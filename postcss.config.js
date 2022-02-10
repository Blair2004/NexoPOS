const fs  = require( 'fs' );
const wildcard  = require( 'wildcard' );
const path  = require( 'path' );
const directory   = path.join( __dirname, 'public/css' );

// postcss.config.js
module.exports = (ctx) => {
  const files   = fs.readdirSync( directory );
  
  files.forEach( file => {
    if ( 
      ( wildcard( 'app.*.css', file ) || wildcard( 'app.css', file ) ) ||
      ( wildcard( 'dark.*.css', file ) || wildcard( 'dark.css', file ) ) ||
      ( wildcard( 'light.*.css', file ) || wildcard( 'light.css', file ) ) ||
      ( wildcard( 'fonts.*.css', file ) || wildcard( 'fonts.css', file ) ) ||
      ( wildcard( 'typography.*.css', file ) || wildcard( 'typography.css', file ) ) ||
      ( wildcard( 'animations.*.css', file ) || wildcard( 'animations.css', file ) )
    ) {
      fs.unlinkSync( `${__dirname}/public/css/${file}` );
    }
  });

  console.log( `${ ctx.env === 'production' ? 'compiling' : 'watching' }: ${ctx.file.basename}` );
  
  return {
    syntax: 'postcss-scss',
    parser: 'postcss-scss',
    plugins: {
      'autoprefixer': {},
      'postcss-import': {},
      'postcss-hash': ctx.env === 'production' ? {
        algorithm: 'sha256',
          trim: 20,
          manifest: './public/css-manifest.json'
      } : false,
      'tailwindcss/nesting' : {},
      'tailwindcss': require('tailwindcss'),      
      'cssnano': ctx.env === 'production' ? {} : false
    }
  };
}