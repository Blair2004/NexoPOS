import { shallowRef } from "vue";

declare const nsExtraComponents;

export default class FormValidation {
    validateFields( fields ) {
        const extractedFields = this.extractFields( fields );
        const labels         = this.extractFieldsLabels( fields );
        
        return fields.map( field => {
            this.checkField( field, extractedFields, labels, { touchField: false } );
            return field.errors ? field.errors.length === 0 : 0;
        }).filter( f => f === false ).length === 0;
    }

    validateFieldsErrors( fields, form ) {
        const extractedForm = this.extractForm( form );
        const labels         = this.extractFormLabels( form );

        return fields.map( field => {
            this.checkField( field, extractedForm, labels, { touchField: false });
            return field.errors;
        }).flat();
    }

    validateForm( form ) {

        const globalErrors          =   [];

        if ( form.main ) {
            this.validateField( form.main );
        }
        
        for( let key in form.tabs ) {
            /**
             * Only tabs having fields can be verified.
             */
            const tabsInvalidity    =   [];
            form.tabs[ key ].errors =   [];

            /**
             * if the tabs has some fields
             * we'll make a check on theses
             */
            if ( form.tabs[ key ].fields ) {
                const validErrors       =   this.validateFieldsErrors( form.tabs[ key ].fields, form );
                
                if ( validErrors.length > 0 ) {
                    tabsInvalidity.push(
                        validErrors
                    );

                    /**
                     * This will add a notification on the tab
                     * to indicate the user that there are errors
                     */
                    form.tabs[ key ].errors.push( ...validErrors );
                }

                globalErrors.push( tabsInvalidity.flat() );
            }

            const instance     =   form.tabs[ key ].instance;

            if ( instance && instance.errors.length > 0 ) {
                globalErrors.push( ...instance.errors );
            }
        }

        return globalErrors.flat().filter( error => error !== undefined );
    }

    initializeTabs( tabs ) {
        let index           =   0;

        for( let key in tabs ) {
            if ( index === 0 ) {
                tabs[ key ].active  =   true;
            }

            tabs[ key ].active     =   tabs[ key ].active === undefined ? false : tabs[ key ].active;
            tabs[ key ].fields     =   this.createFields( tabs[ key ].fields );

            index++;
        }

        return tabs;
    }

    validateField( field ) {
        return this.checkField( field, [], { touchField: false } );
    }

    fieldsValid( fields ) {
        return ! ( fields.map( field => field.errors && field.errors.length > 0 )
            .filter( f => f ).length > 0 );
    }

    createFields( fields ) {
        return fields.map( field => {
            field.type      =   field.type      || 'text',
            field.errors    =   field.errors    || [];
            field.disabled  =   field.disabled  || false;
            field.touched   =   false;            
            return field;
        });
    }

    /**
     * Checks wether a for is touched or not.
     * @param form current form to perform the check
     * @returns bool
     */
    isFormUntouched( form ) {
        let isFormUntouched = true;

        if ( form.main ) {
            isFormUntouched     =   form.main.touched ? false : isFormUntouched;
        }

        if ( form.tabs ) {
            for( let tab in form.tabs ) {
                isFormUntouched =   form.tabs[ tab ].fields.filter( field => field.touched ).length > 0 ? false : isFormUntouched;
            }
        }

        return isFormUntouched;
    }

    createForm( form ) {
        if ( form.main ) {
            form.main   =   this.createFields([ form.main ])[0];
        }

        if ( form.tabs ) {
            for( let tab in form.tabs ) {
                form.tabs[ tab ].errors     =   [];
                
                /**
                 * a tab might not have fields. In such case we should 
                 * skip creating fields and try building component.
                 */
                if ( form.tabs[ tab ].fields !== undefined ) {
                    form.tabs[ tab ].fields     =   this.createFields( form.tabs[ tab ].fields );
                } else {
                    console.info( `Warning: The tab "${form.tabs[ tab ].label}" is missing fields. Fallback on checking dynamic component instead.` )
                }
            }
        }

        return form;
    }

    enableFields( fields ) {
        return fields.map( field => field.disabled = false );
    }

    disableFields( fields ) {
        return fields.map( field => field.disabled = true );
    }

    disableForm( form ) {
        if ( form.main ) {
            form.main.disabled  =   true;
        }

        for( let tab in form.tabs ) {
            form.tabs[ tab ].fields.forEach( field => field.disabled = true );
        }
    }

    enableForm( form ) {
        if ( form.main ) {
            form.main.disabled  =   false;
        }

        for( let tab in form.tabs ) {
            form.tabs[ tab ].fields.forEach( field => field.disabled = false );
        }
    }

    getValue( fields ) {
        const form  =   {};
        fields.forEach( field => {
            form[ field.name ]  =   field.value;
        });

        return form;
    }

    checkField( field, form = {}, labels = {}, options = {
        touchField: true
    } ) {
        if ( field.validation !== undefined ) {
            field.errors    =   [];
            const rules     =   this.detectValidationRules( field.validation ).filter( rule => rule != undefined );
            const ruleNames =   rules.map( rule => rule.identifier );

            /**
             * when the rule "sometimes" is defined. The field will be processed only if there is a value provided.
             */
            if ( ruleNames.includes( 'sometimes' ) ) {
                if ( ! [ undefined, null ].includes( field.value ) && field.value.length > 0 ) {
                    rules.forEach( rule => {
                        this.fieldPassCheck( field, rule, form, labels );
                    });
                }
            } else {
                rules.forEach( rule => {
                    this.fieldPassCheck( field, rule, form, labels );
                });
            }
        }

        /**
         * By default, the field is not touched when
         * we want to perform a verification. But 
         * that option can enable a touching behavior.
         */
        if ( options.touchField ) {
            field.touched = true;
        }

        return field;
    }

    extractForm( form ) {
        let formValue  =   {};

        if ( form.main ) {
            formValue[ form.main.name ]     =   form.main.value;
        }

        if ( form.tabs ) {
            for( let tab in form.tabs ) {
                if ( formValue[ tab ] === undefined ) {
                    formValue[ tab ]    =   {};
                }
    
                formValue[ tab ]   =   this.extractFields( form.tabs[ tab ].fields );
            }
        }

        return formValue;
    }

    extractFields( fields, formValue = {} ) {
        fields.forEach( field => {
            formValue[ field.name ]  =   field.value;
        });

        return formValue;
    }

    extractFormLabels( form ) {
        let formValue  =   {};
        if ( form.main ) {
            formValue[ form.main.name ]     =   form.main.label;
        }
        if ( form.tabs ) {
            for( let tab in form.tabs ) {
                if ( formValue[ form.tabs[ tab ].identifier ] === undefined ) {
                    formValue[ form.tabs[ tab ].identifier ]    =   {};
                }
                formValue[ form.tabs[ tab ].identifier ]   =   this.extractFieldsLabels( form.tabs[ tab ].fields );
            }
        }
        return formValue;
    }

    extractFieldsLabels( fields, formValue = {} ) {
        fields.forEach( field => {
            formValue[ field.name ]  =   field.label;
        });

        return formValue;
    }

    detectValidationRules(validation) {
        const execRule = (rule) => {
            const minRule = /(min):([0-9]+)/;
            const maxRule = /(max):([0-9]+)/;
            const sameRule = /(same):([^|]+)/;
            const diffRule = /(different):([^|]+)/;
            const sometimesRule = /(sometimes)/;
            const regexRule = /(regex):\/(.+?)\/(?=\||$)/; // Update regex parsing
            const number = /(number)/;
    
            let result;
    
            if (['email', 'required'].includes(rule)) {
                return { identifier: rule };
            } else if (rule.length > 0) {
                result =
                    minRule.exec(rule) ||
                    maxRule.exec(rule) ||
                    sameRule.exec(rule) ||
                    diffRule.exec(rule) ||
                    sometimesRule.exec(rule) ||
                    regexRule.exec(rule) ||
                    number.exec( rule );
    
                if (result !== null) {
                    return {
                        identifier: result[1],
                        value: result[2]
                    };
                }
            }
        };
    
        if (Array.isArray(validation)) {
            return validation.filter(r => typeof r === 'string').map(execRule);
        } else {
            return validation.split('|').map(execRule);
        }
    }
    

    /**
     * Will trigger an error on the form
     * if the error is a validation object.
     * @param {Object} Form 
     * @param {Object} data 
     */
    triggerError( form, data ) {
        if ( data && data.errors ) {
            for( let index in data.errors ) {
                let path    =   index.split( '.' ).filter( exp => {
                    return ! /^\d+$/.test( exp );
                });

                /**
                 * if the validation path
                 * has 2 entries we believe it's a 
                 * an error on a field within a tab
                 */
                if ( path.length === 2 ) {
                    form.tabs[ path[0] ].fields.forEach( field => {
                        if ( field.name === path[1] ) {
                            data.errors[ index ].forEach( errorMessage => {
                                const error     =   {
                                    identifier: 'invalid',
                                    invalid: true,
                                    message: errorMessage,
                                    name: field.name
                                };

                                field.errors.push( error );
                            });
                        }
                    });
                }

                /**
                 * @todo needs to do the same with the title
                 */
                if ( index === form.main.name ) {
                    data.errors[ index ].forEach( errorMessage => {
                        form.main.errors.push({
                            identifier: 'invalid',
                            invalid: true,
                            message: errorMessage,
                            name: form.main.name
                        });
                    });
                }
            }
        }
    }

    triggerFieldsErrors( fields, data ) {
        if ( data && data.errors ) {
            for( let fieldName in data.errors ) {
                /**
                 * if the validation path
                 * has 2 entries we believe it's a 
                 * an error on a field within a tab
                 */
                fields.forEach( field => {
                    if ( field.name === fieldName ) {
                        data.errors[ fieldName ].forEach( errorMessage => {
                            const error     =   {
                                identifier: 'invalid',
                                invalid: true,
                                message: errorMessage,
                                name: field.name
                            };

                            field.errors.push( error );
                        });
                    }
                });
            }
        }
    }

    trackError( field, rule, form, labels ) {
        field.errors.push({
            identifier: rule.identifier,
            invalid: true,
            name: field.name,
            rule,
            form,
            labels,
        })
    }

    unTrackError( field, rule ) {
        field.errors.forEach( ( error, index ) => {
            if ( error.identifier === rule.identifier && error.invalid === true ) {
                field.errors.splice( index, 1 );
            }
        });
    }

    private getFieldValue( field ) {
        let fieldValue = field.value;

        /**
         * We case we have a field that include prefix and suffix, we'll
         * introduce validation with prefix and suffix.
         */
        if ( field.data && field.data['validate-with-suffix'] ) {
            fieldValue  +=  field.suffix || '';
        }

        if ( field.data && field.data[ 'validate-with-prefix' ] ) {
            fieldValue  =   ( field.prefix || '' ) + fieldValue;
        }
        return fieldValue;
    }

    /**
     * This methods defines a set of rules that must returns "true" to 
     * indicate the field check was not successful.
     * @param field field object
     * @param rule define rule
     * @param form sibling fields
     * @returns any
     */
    fieldPassCheck(field, rule, form, labels ) {
        if (rule !== undefined) {
            const rules = {
                required: (field, rule) =>
                    field.value === undefined || field.value === null || field.value.length === 0,
    
                email: (field, rule) =>
                    field.value !== undefined && field.value.length > 0 && !/^[\w.-]+@([\w-]+\.)+[\w-]{2,}$/i.test(field.value),
    
                same: (field, rule) => {
                    const similar = this.getValueByDotNotation( form, rule.value );
                    const ruleHasWildcard = rule.value.includes('*'); 
                    const fieldValue = this.getFieldValue( field );
                    return ( ! ruleHasWildcard && `${similar}`.length > 0 && fieldValue !== similar );
                },
    
                different: (field, rule) => {
                    const similar = this.getValueByDotNotation( form, rule.value );
                    const ruleHasWildcard = rule.value.includes('*'); 
                    const fieldValue = this.getFieldValue( field );
                    return ! ruleHasWildcard && similar && fieldValue === similar;
                },
    
                min: (field, rule) => {
                    const fieldValue = this.getFieldValue( field );
                    return ! [ null, undefined ].includes( fieldValue ) && fieldValue.length < parseInt(rule.value);
                },

                max: (field, rule) => {
                    const fieldValue = this.getFieldValue( field );
                    return ! [ null, undefined ].includes( fieldValue ) && fieldValue.length > parseInt(rule.value);
                },
    
                regex: (field, rule) => {
                    const fieldValue = this.getFieldValue( field );
                    const pattern = new RegExp(rule.value);
                    return !pattern.test(fieldValue || '');
                },

                number: ( field, rule ) => {
                    const fieldValue = this.getFieldValue( field );
                    return !/^[0-9]+$/.test( fieldValue )
                }
            };
    
            const ruleValidated = rules[rule.identifier];
    
            if (typeof ruleValidated === 'function') {
                if (ruleValidated(field, rule) === false) {
                    return this.unTrackError(field, rule);
                } else {
                    return this.trackError(field, rule, form, labels );
                }
            }
    
            return field;
        }
    }

    /**
     * Get a value from an object using dot notation.
     * @param {Object} obj - The object to search.
     * @param {string} path - The dot notation path to the value.
     * @returns {any} - The value at the specified path, or undefined if not found.
     */
    getValueByDotNotation(obj, path) {
        return path.split('.').reduce((acc, key) => {
            if (acc && typeof acc === 'object') {
                return acc[key];
            }
            return undefined;
        }, obj);
    }
}