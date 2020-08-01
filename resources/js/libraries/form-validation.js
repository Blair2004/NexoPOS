export default class FormValidation {
    validateFields( fields ) {
        return fields.map( field => {
            this.checkField( field );
            return field.errors.length === 0;
        }).filter( f => f === false ).length === 0;
    }

    validateForm( form ) {
        this.validateField( form.main );

        const tabsInvalidity    =   [];

        for( let key in form.tabs ) {
            tabsInvalidity.push(
                this.validateFields( form.tabs[ key ].fields )
            );
        }

        if ( form.main.errors.length > 0 || tabsInvalidity.filter( f => f === false ).length > 0 ) {
            return false;
        }

        return true;
    }

    validateField( field ) {
        return this.checkField( field );
    }

    createForm( fields ) {
        return fields.map( field => {
            field.type      =   field.type || 'text',
            field.errors    =   field.errors || [];
            field.disabled  =   field.disabled || false;
            return field;
        })
    }

    enableFields( fields ) {
        return fields.map( field => field.disabled = false );
    }

    disableFields( fields ) {
        return fields.map( field => field.disabled = true );
    }

    disableForm( form ) {
        form.main.disabled  =   true;
        for( let tab in form.tabs ) {
            form.tabs[ tab ].fields.forEach( field => field.disabled = true );
        }
        console.log( form.main.disabled );
    }

    enableForm( form ) {
        form.main.disabled  =   false;
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
        const formValue  =   {};
        formValue[ form.main.name ]     =   form.main.value;

        for( let tab in form.tabs ) {
            if ( formValue[ tab ] === undefined ) {
                formValue[ tab ]    =   {};
            }

            form.tabs[ tab ].fields.forEach( field => {
                formValue[ tab ][ field.name ]  =   field.value;
            });
        }

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
        console.log( data );
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
                                field.errors.push({
                                    identifier: 'invalid',
                                    invalid: true,
                                    message: errorMessage
                                });
                            });
                        }
                    })
                }

                /**
                 * @todo needs to do the same with the title
                 */
            }
        }
    }

    fieldPassCheck( field, rule ) {

        if ( rule.identifier === 'required' ) {
            if ( field.value === undefined || field.value.length === 0 ) {
                // because we would like to stop the validation here
                return field.errors.push({
                    identifier: rule.identifier,
                    invalid: true
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
                    invalid: true
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