export default class FormValidation {
    validateFields( fields ) {
        return fields.map( field => {
            this.checkField( field );
            return field.errors.length === 0;
        }).filter( f => f === false ).length === 0;
    }

    validateFieldsErrors( fields ) {
        return fields.map( field => {
            this.checkField( field );
            return field.errors;
        }).flat();
    }

    validateForm( form ) {
        if ( form.main ) {
            this.validateField( form.main );
        }

        const globalErrors          =   [];
        
        for( let key in form.tabs ) {
            const tabsInvalidity    =   [];
            const validErrors       =   this.validateFieldsErrors( form.tabs[ key ].fields );
            
            if ( validErrors.length > 0 ) {
                tabsInvalidity.push(
                    validErrors
                );
            }

            form.tabs[ key ].errors     =   tabsInvalidity.flat();
            globalErrors.push( tabsInvalidity.flat() );
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
        return this.checkField( field );
    }

    createFields( fields ) {
        return fields.map( field => {
            field.type      =   field.type      || 'text',
            field.errors    =   field.errors    || [];
            field.disabled  =   field.disabled  || false;
            return field;
        })
    }

    createForm( form ) {
        if ( form.main ) {
            form.main   =   this.createFields([ form.main ])[0];
        }

        if ( form.tabs ) {
            for( let tab in form.tabs ) {
                form.tabs[ tab ].fields     =   this.createFields( form.tabs[ tab ].fields );
                form.tabs[ tab ].errors     =   [];
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

    checkField( field ) {
        if ( field.validation !== undefined ) {
            field.errors    =   [];
            const rules     =   this.detectValidationRules( field.validation );
            rules.forEach( rule => {
                this.fieldPassCheck( field, rule );
            });
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
            if ( [ 'multiselect' ].includes( field.type ) ) {
                formValue[ field.name ]  =   field.options
                    .filter( option => option.selected )
                    .map( option => option.value );
            } else {
                formValue[ field.name ]  =   field.value;
            }
        });

        return formValue;
    }

    detectValidationRules( validation ) {
        const execRule  =   ( rule ) => {
            let finalRules          =   [];
            const minRule 			=	/(min)\:([0-9])+/g;
            const maxRule 			=	/(max)\:([0-9])+/g;
            const matchRule         =   /(same):(\w+)/g;
            let result;

            if ([ 'email', 'required' ].includes( rule ) ) {
                return {
                    identifier : rule
                };
            } else if( result =   minRule.exec( rule ) ) {
                return {
                    identifier : result[1],
                    value: result[2]
                }
            } else if( result =   maxRule.exec( rule ) ) {
                return {
                    identifier : result[1],
                    value: result[2]
                }
            }
            
            return rule;
        };

        if ( Array.isArray( validation ) ) {
            return validation.filter( r => typeof r === 'string' )
                .map( execRule );
        } else {
            return validation.split( '|' ).map( execRule );
        }
    }

    /**
     * Will trigger an error on the form
     * if the error is a validation object.
     * @param {Object} Form 
     * @param {Object} data 
     */
    triggerError( form, data ) {
        if ( data.errors ) {
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

    fieldPassCheck( field, rule ) {

        if ( rule.identifier === 'required' ) {
            if ( field.value === undefined || field.value === null || field.value.length === 0 ) {
                // because we would like to stop the validation here
                return field.errors.push({
                    identifier: rule.identifier,
                    invalid: true,
                    name: field.name
                })
            } else {
                field.errors.forEach( ( error, index ) => {
                    if ( error.identifier === rule.identifier && error.invalid === true ) {
                        field.errors.splice( index, 1 );
                        console.log( field );
                    }
                });
            }
        }

        if ( rule.identifier === 'email' ) {
            if ( ! /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test( field.value ) ) {
                // because we would like to stop the validation here
                return field.errors.push({
                    identifier: rule.identifier,
                    invalid: true,
                    name: field.name
                })
            } else {
                field.errors.forEach( ( error, index ) => {
                    if ( error[ rule.identifier ] === true ) {
                        field.errors.splice( index, 1 );
                        console.log( field );
                    }
                });
            }
        }

        return field;
    }
}