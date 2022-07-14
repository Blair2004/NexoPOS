export default {
    install( app, options ) {
        app.config.globalProperties.$popup          =   options.$popup;
        app.config.globalProperties.$popupParams    =   options.$popupParams;
    }
}