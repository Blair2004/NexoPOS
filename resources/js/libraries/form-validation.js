export default class FormValidation {
    validateForm( form ) {
        return form.map( field => {
            this.checkField( field );
            return field.errors.length === 0;
        }).filter( f => f === false ).length === 0;
    }

    validateField( field ) {
        return this.checkField( field );
    }

    createForm( fields ) {
        return fields.map( field => {
            field.type      =   field.type || 'text',
            field.errors    =   field.errors || [];
            return field;
        })
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
            const rules     =   this.detectValidationRules( field.validation );
            rules.forEach( rule => {
                this.fieldPassCheck( field, rule );
            });
        }
        return field;
    }

    detectValidationRules( validation ) {
        return validation.split( '|' ).map( rule => {
            if ([ 'email', 'required' ].includes( rule ) ) {
                return {
                    identifier : rule
                };
            }
            return rule;
        });
    }

    fieldPassCheck( field, rule ) {
        if ( field.errors === undefined ) {
            field.errors    =   [];
        }

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