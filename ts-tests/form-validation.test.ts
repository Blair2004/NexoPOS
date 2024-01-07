import {describe, expect, test} from '@jest/globals';

import FormValidation from "../resources/ts/libraries/form-validation";

describe( 'Tests Form Validation Class', () => {
    test( 'Invalid Field Check', () => {
        let fields  =   [{
            label: 'Test',
            value: null,
            name: 'field_name',
            validation: 'required'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( false );
    });

    test( 'Valid Field Check', () => {
        let fields  =   [{
            label: 'Test',
            value: 'hello world',
            name: 'field_name',
            validation: 'required'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( true );
    })

    test( 'Valid Min Length Check', () => {
        let fields  =   [{
            label: 'Test',
            value: '123456',
            name: 'field_name',
            validation: 'min:6'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( true );
    })

    test( 'Invalid Min Length Check', () => {
        let fields  =   [{
            label: 'Test',
            value: '12345',
            name: 'field_name',
            validation: 'min:6'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( false );
    })

    test( 'Valid Max Length Check', () => {
        let fields  =   [{
            label: 'Test',
            value: '123456',
            name: 'field_name',
            validation: 'max:6'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( true );
    })

    test( 'Invalid Max Length Check', () => {
        let fields  =   [{
            label: 'Test',
            value: '1234567',
            name: 'field_name',
            validation: 'max:6'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( false );
    })

    test( 'Valid Email Check', () => {
        let fields  =   [{
            label: 'Test',
            value: 'contact@nexopos.com',
            name: 'field_name',
            validation: 'email'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( true );
    })

    test( 'Invalid Email Check', () => {
        let fields  =   [{
            label: 'Test',
            value: 'contactnexopos.com',
            name: 'field_name',
            validation: 'email'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( false );
    })

    test( 'Valid Same Check', () => {
        let fields  =   [{
            label: 'First Field',
            value: 'contactnexopos.com',
            name: 'first_field',
            validation: 'required'
        }, {
            label: 'Second Field',
            value: 'contactnexopos.com',
            name: 'second_field',
            validation: 'same:first_field'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( true );
    })

    test( 'Invalid Same Check', () => {
        let fields  =   [{
            label: 'First Field',
            value: 'contactnexopos.com',
            name: 'first_field',
            validation: 'required'
        }, {
            label: 'Second Field',
            value: 'different text',
            name: 'second_field',
            validation: 'same:first_field'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( false );
    })

    test( 'Valid Different Check', () => {
        let fields  =   [{
            label: 'First Field',
            value: 'contactnexopos.com',
            name: 'first_field',
            validation: 'required'
        }, {
            label: 'Second Field',
            value: 'different text',
            name: 'second_field',
            validation: 'different:first_field'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( true );
    })

    test( 'Invalid Different Check', () => {
        let fields  =   [{
            label: 'First Field',
            value: 'contactnexopos.com',
            name: 'first_field',
            validation: 'required'
        }, {
            label: 'Second Field',
            value: 'contactnexopos.com',
            name: 'second_field',
            validation: 'different:first_field'
        }];

        const validation    =   new FormValidation;

        expect( validation.validateFields( fields ) ).toBe( false );
    })
});