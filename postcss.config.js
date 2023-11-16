const fs  = require( 'fs' );
const wildcard  = require( 'wildcard' );
const path  = require( 'path' );
const directory   = path.join( __dirname, 'public/css' );

// postcss.config.js
module.exports = (ctx) => {
  const files   = fs.readdirSync( directory );

  if ( ctx.file.basename ) {
    files.forEach( file => {
      if ( file.split( '.' )[0] === ctx.file.basename.split( '.' )[0] ) {
        if ( 
          ( wildcard( 'app.*.css', file ) || wildcard( 'app.css', file ) ) ||
          ( wildcard( 'dark.*.css', file ) || wildcard( 'dark.css', file ) ) ||
          ( wildcard( 'light.*.css', file ) || wildcard( 'light.css', file ) ) ||
          ( wildcard( 'fonts.*.css', file ) || wildcard( 'fonts.css', file ) ) ||
          ( wildcard( 'typography.*.css', file ) || wildcard( 'typography.css', file ) ) ||
          ( wildcard( 'animations.*.css', file ) || wildcard( 'animations.css', file ) )
        ) {
          const path  = `${__dirname}/public/css/${file}`;
          if( fs.existsSync( path ) ) {
            fs.unlinkSync( path );
          }
        }
      }
    });
  
    console.log( `${ ctx.env === 'production' ? 'compiling' : 'watching' }: ${ ctx.file.basename || path.basename( ctx.file ) }` );
    
    return {
      syntax: 'postcss-scss',
      parser: 'postcss-scss',
      plugins: {
        'postcss-css-variables': {},
        'postcss-advanced-variables': {},
        'postcss-import': {},
        'postcss-nesting': {},
        'autoprefixer': {},
        'tailwindcss/nesting': require('tailwindcss/nesting'),
        'tailwindcss': require('tailwindcss'),      
        'postcss-hash': ctx.env === 'production' ? {
          algorithm: 'sha256',
            trim: 20,
            manifest: './public/css-manifest.json'
        } : false,
        'cssnano': ctx.env === 'production' ? {} : false
      }
    };
  }
}