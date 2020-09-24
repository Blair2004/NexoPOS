var FormValidation = /** @class */ (function () {
    function FormValidation() {
    }
    FormValidation.prototype.validateFields = function (fields) {
        var _this = this;
        return fields.map(function (field) {
            _this.checkField(field);
            return field.errors.length === 0;
        }).filter(function (f) { return f === false; }).length === 0;
    };
    FormValidation.prototype.validateFieldsErrors = function (fields) {
        var _this = this;
        return fields.map(function (field) {
            _this.checkField(field);
            return field.errors;
        }).flat();
    };
    FormValidation.prototype.validateForm = function (form) {
        if (form.main) {
            this.validateField(form.main);
        }
        var globalErrors = [];
        for (var key in form.tabs) {
            var tabsInvalidity = [];
            var validErrors = this.validateFieldsErrors(form.tabs[key].fields);
            if (validErrors.length > 0) {
                tabsInvalidity.push(validErrors);
            }
            form.tabs[key].errors = tabsInvalidity.flat();
            globalErrors.push(tabsInvalidity.flat());
        }
        return globalErrors.flat().filter(function (error) { return error !== undefined; });
    };
    FormValidation.prototype.validateField = function (field) {
        return this.checkField(field);
    };
    FormValidation.prototype.createFields = function (fields) {
        return fields.map(function (field) {
            field.type = field.type || 'text',
                field.errors = field.errors || [];
            field.disabled = field.disabled || false;
            return field;
        });
    };
    FormValidation.prototype.createForm = function (form) {
        if (form.main) {
            form.main = this.createFields([form.main])[0];
        }
        if (form.tabs) {
            for (var tab in form.tabs) {
                form.tabs[tab].fields = this.createFields(form.tabs[tab].fields);
                form.tabs[tab].errors = [];
            }
        }
        return form;
    };
    FormValidation.prototype.enableFields = function (fields) {
        return fields.map(function (field) { return field.disabled = false; });
    };
    FormValidation.prototype.disableFields = function (fields) {
        return fields.map(function (field) { return field.disabled = true; });
    };
    FormValidation.prototype.disableForm = function (form) {
        if (form.main) {
            form.main.disabled = true;
        }
        for (var tab in form.tabs) {
            form.tabs[tab].fields.forEach(function (field) { return field.disabled = true; });
        }
    };
    FormValidation.prototype.enableForm = function (form) {
        if (form.main) {
            form.main.disabled = false;
        }
        for (var tab in form.tabs) {
            form.tabs[tab].fields.forEach(function (field) { return field.disabled = false; });
        }
    };
    FormValidation.prototype.getValue = function (fields) {
        var form = {};
        fields.forEach(function (field) {
            form[field.name] = field.value;
        });
        return form;
    };
    FormValidation.prototype.checkField = function (field) {
        var _this = this;
        if (field.validation !== undefined) {
            field.errors = [];
            var rules = this.detectValidationRules(field.validation);
            rules.forEach(function (rule) {
                _this.fieldPassCheck(field, rule);
            });
        }
        return field;
    };
    FormValidation.prototype.extractForm = function (form) {
        var formValue = {};
        if (form.main) {
            formValue[form.main.name] = form.main.value;
        }
        if (form.tabs) {
            for (var tab in form.tabs) {
                if (formValue[tab] === undefined) {
                    formValue[tab] = {};
                }
                formValue[tab] = this.extractFields(form.tabs[tab].fields);
            }
        }
        return formValue;
    };
    FormValidation.prototype.extractFields = function (fields, formValue) {
        if (formValue === void 0) { formValue = {}; }
        fields.forEach(function (field) {
            if (['multiselect'].includes(field.type)) {
                formValue[field.name] = field.options
                    .filter(function (option) { return option.selected; })
                    .map(function (option) { return option.value; });
            }
            else {
                formValue[field.name] = field.value;
            }
        });
        return formValue;
    };
    FormValidation.prototype.detectValidationRules = function (validation) {
        var execRule = function (rule) {
            var finalRules = [];
            var minRule = /(min)\:([0-9])+/g;
            var maxRule = /(max)\:([0-9])+/g;
            var matchRule = /(same):(\w+)/g;
            var result;
            if (['email', 'required'].includes(rule)) {
                return {
                    identifier: rule
                };
            }
            else if (result = minRule.exec(rule)) {
                return {
                    identifier: result[1],
                    value: result[2]
                };
            }
            else if (result = maxRule.exec(rule)) {
                return {
                    identifier: result[1],
                    value: result[2]
                };
            }
            return rule;
        };
        if (Array.isArray(validation)) {
            return validation.filter(function (r) { return typeof r === 'string'; })
                .map(execRule);
        }
        else {
            return validation.split('|').map(execRule);
        }
    };
    /**
     * Will trigger an error on the form
     * if the error is a validation object.
     * @param {Object} Form
     * @param {Object} data
     */
    FormValidation.prototype.triggerError = function (form, data) {
        if (data.errors) {
            var _loop_1 = function (index) {
                var path = index.split('.').filter(function (exp) {
                    return !/^\d+$/.test(exp);
                });
                /**
                 * if the validation path
                 * has 2 entries we believe it's a
                 * an error on a field within a tab
                 */
                if (path.length === 2) {
                    form.tabs[path[0]].fields.forEach(function (field) {
                        if (field.name === path[1]) {
                            data.errors[index].forEach(function (errorMessage) {
                                var error = {
                                    identifier: 'invalid',
                                    invalid: true,
                                    message: errorMessage,
                                    name: field.name
                                };
                                field.errors.push(error);
                            });
                        }
                    });
                }
                /**
                 * @todo needs to do the same with the title
                 */
                if (index === form.main.name) {
                    data.errors[index].forEach(function (errorMessage) {
                        form.main.errors.push({
                            identifier: 'invalid',
                            invalid: true,
                            message: errorMessage,
                            name: form.main.name
                        });
                    });
                }
            };
            for (var index in data.errors) {
                _loop_1(index);
            }
        }
    };
    FormValidation.prototype.fieldPassCheck = function (field, rule) {
        if (rule.identifier === 'required') {
            if (field.value === undefined || field.value === null || field.value.length === 0) {
                // because we would like to stop the validation here
                return field.errors.push({
                    identifier: rule.identifier,
                    invalid: true,
                    name: field.name
                });
            }
            else {
                field.errors.forEach(function (error, index) {
                    if (error.identifier === rule.identifier && error.invalid === true) {
                        field.errors.splice(index, 1);
                        console.log(field);
                    }
                });
            }
        }
        if (rule.identifier === 'email') {
            if (!/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(field.value)) {
                // because we would like to stop the validation here
                return field.errors.push({
                    identifier: rule.identifier,
                    invalid: true,
                    name: field.name
                });
            }
            else {
                field.errors.forEach(function (error, index) {
                    if (error[rule.identifier] === true) {
                        field.errors.splice(index, 1);
                        console.log(field);
                    }
                });
            }
        }
        return field;
    };
    return FormValidation;
}());
export default FormValidation;
//# sourceMappingURL=form-validation.js.map