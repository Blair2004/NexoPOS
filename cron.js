/**
 * this is expecially designed
 * for windows using with Node.js installed
 */

const child_process     =   require( 'child_process' );
const process           =   child_process.exec( `php ${__dirname}\\artisan queue:work` );

console.info( 'Starting the cron job. Press CTRL+C to exit...' );

process.stdout.on( 'data', ( data ) => {
    console.log( data );
});

process.on( 'error', ( error ) => {
    console.log( error );
})