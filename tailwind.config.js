const colors  = require( 'tailwindcss/colors' );

function withOpacityValue(variable) {
  return ({ opacityValue }) => {
    if (opacityValue === undefined) {
      return `rgb(var(${variable}))`
    }
    return `rgb(var(${variable}) / ${opacityValue})`
  }
}

const heightDims = {
  '6/7-screen': '85.71vh',
  '5/7-screen': '71.42vh',
  '4/7-screen': '57.14vh',
  '3/7-screen': '42.85vh',
  '2/7-screen': '28.57vh',
  '1/7-screen': '14.28vh',
  '5/6-screen': '83.33vh',
  '4/6-screen': '66.66vh',
  '3/6-screen': '50vh',
  '2/6-screen': '33.33vh',
  '1/6-screen': '16.66vh',
  '4/5-screen': '80vh',
  '3/5-screen': '60vh',
  '2/5-screen': '40vh',
  '1/5-screen': '20vh',
  '3/4-screen': '75vh',
  '2/4-screen': '50vh',
  '1/4-screen': '25vh',
  '2/3-screen': '66.66vh',
  '1/3-screen': '33.33vh',
  'half': '50vh',
  '95vh': '95vh'
};

const widthDims = {
  '6/7-screen': '85.71vw',
  '5/7-screen': '71.42vw',
  '4/7-screen': '57.14vw',
  '3/7-screen': '42.85vw',
  '2/7-screen': '28.57vw',
  '1/7-screen': '14.28vw',
  '5/6-screen': '83.33vw',
  '4/6-screen': '66.66vw',
  '3/6-screen': '50vw',
  '2/6-screen': '33.33vw',
  '1/6-screen': '16.66vw',
  '4/5-screen': '80vw',
  '3/5-screen': '60vw',
  '2/5-screen': '40vw',
  '1/5-screen': '20vw',
  '3/4-screen': '75vw',
  '2/4-screen': '50vw',
  '1/4-screen': '25vw',
  '2/3-screen': '66.66vw',
  '1/3-screen': '33.33vw',
  'half': '50vw',
  '95vw': '95vw'
}

module.exports = {
  content: [
    './app/Crud/**/*.php',
    './resources/**/*.{vue,ts,php}',
  ],
  darkMode: 'class',
  corePlugins: {
    float: false
  },
  important: true,
  theme: {
    fontFamily: {
      display: ['Gilroy', 'sans-serif'],
      body: ['Graphik', 'sans-serif'],
    },
    extend: {
      colors: {
        teal: colors.teal,
        orange: colors.orange,
        cyan: colors.cyan,
        
        typography: withOpacityValue('--typography'),
        
        surface: withOpacityValue('--surface'),
        'surface-soft': withOpacityValue('--surface-soft'),
        'surface-hard': withOpacityValue('--surface-hard'),
        'popup-surface': withOpacityValue('--popup-surface'),

        'input-edge': withOpacityValue('--input-edge'),
        'input-background': withOpacityValue('--input-background'),
        'input-disabled': withOpacityValue('--input-disabled'),
        'input-button': withOpacityValue('--input-button'),
        'input-button-hover': withOpacityValue('--input-button-hover'),
        'input-button-active': withOpacityValue('--input-button-active'),
        'input-option-hover': withOpacityValue('--input-option-hover'),

        'box-background': withOpacityValue('--box-background'),
        'box-edge': withOpacityValue('--box-edge'),
        'box-elevation-background': withOpacityValue('--box-elevation-background'),
        'box-elevation-edge': withOpacityValue('--box-elevation-edge'),
        'box-elevation-hover': withOpacityValue('--box-elevation-hover'),

        'option-hover': withOpacityValue('--option-hover'),
        'crud-button-edge': withOpacityValue('--crud-button-edge'),
        'crud-input-background': withOpacityValue('--crud-input-background'),
        'pos-button-edge': withOpacityValue('--pos-button-edge'),

        'numpad-typography': withOpacityValue('--numpad-typography'),
        'numpad-edge': withOpacityValue('--numpad-edge'),
        'numpad-hover': withOpacityValue('--numpad-hover'),
        'numpad-hover-edge': withOpacityValue('--numpad-hover-edge'),

        'tab-table-th': withOpacityValue('--tab-table-th'),
        'tab-table-th-edge': withOpacityValue('--tab-table-th-edge'),
        'table-th': withOpacityValue('--table-th'),
        'table-th-edge': withOpacityValue('--table-th-edge'),

        'scroll-thumb': withOpacityValue('--scroll-thumb'),
        'scroll-track': withOpacityValue('--scroll-track'),
        'scroll-popup-thumb': withOpacityValue('--scroll-popup-thumb'),
        
        'pre': withOpacityValue('--pre'),

        'tab-active': withOpacityValue('--tab-active'),
        'tab-active-border': withOpacityValue('--tab-active-border'),
        'tab-inactive': withOpacityValue('--tab-inactive'),

        'floating-menu-hover': withOpacityValue('--floating-menu-hover'),
        'floating-menu-selected': withOpacityValue('--floating-menu-selected'),
        'floating-menu': withOpacityValue('--floating-menu'),
        'floating-menu-edge': withOpacityValue('--floating-menu-edge'),

        primary: withOpacityValue('--primary'),
        secondary: withOpacityValue('--secondary'),  
        tertiary: withOpacityValue('--tertiary'),  

        'soft-primary': withOpacityValue('--soft-primary'),
        'soft-secondary': withOpacityValue('--soft-secondary'),
        'soft-tertiary': withOpacityValue('--soft-tertiary'),

        'info-primary': withOpacityValue('--info-primary'),
        'info-secondary': withOpacityValue('--info-secondary'),  
        'info-tertiary': withOpacityValue('--info-tertiary'),  
        
        'info-light-primary': withOpacityValue('--info-light-primary'),  
        'info-light-secondary': withOpacityValue('--info-light-secondary'),  
        'info-light-tertiary': withOpacityValue('--info-light-tertiary'),  
        
        'success-primary': withOpacityValue('--success-primary'),
        'success-secondary': withOpacityValue('--success-secondary'),  
        'success-tertiary': withOpacityValue('--success-tertiary'),  
        
        'success-light-primary': withOpacityValue('--success-light-primary'),  
        'success-light-secondary': withOpacityValue('--success-light-secondary'),  
        'success-light-tertiary': withOpacityValue('--success-light-tertiary'),  
        
        'error-primary': withOpacityValue('--error-primary'),
        'error-secondary': withOpacityValue('--error-secondary'),  
        'error-tertiary': withOpacityValue('--error-tertiary'),  
        
        'error-light-primary': withOpacityValue('--error-light-primary'),  
        'error-light-success': withOpacityValue('--error-light-success'),  
        'error-light-tertiary': withOpacityValue('--error-light-tertiary'),  
        
        'warning-primary': withOpacityValue('--warning-primary'),
        'warning-secondary': withOpacityValue('--warning-secondary'),  
        'warning-tertiary': withOpacityValue('--warning-tertiary'),  
        
        'warning-light-primary': withOpacityValue('--warning-light-primary'),  
        'warning-light-success': withOpacityValue('--warning-light-success'),  
        'warning-light-tertiary': withOpacityValue('--warning-light-tertiary'),  

        'default-primary': withOpacityValue('--default-primary'),
        'default-secondary': withOpacityValue('--default-secondary'),  
        'default-tertiary': withOpacityValue('--default-tertiary'),  
        
        'default-light-primary': withOpacityValue('--default-light-primary'),  
        'default-light-success': withOpacityValue('--default-light-success'),  
        'default-light-tertiary': withOpacityValue('--default-light-tertiary'),  

        'danger-primary': withOpacityValue('--danger-primary'),
        'danger-secondary': withOpacityValue('--danger-secondary'),  
        'danger-tertiary': withOpacityValue('--danger-tertiary'),  
        
        'danger-light-primary': withOpacityValue('--danger-light-primary'),  
        'danger-light-success': withOpacityValue('--danger-light-success'),  
        'danger-light-tertiary': withOpacityValue('--danger-light-tertiary'),  
      },
      fontWeight: [ 'hover', 'focus' ],
      height: heightDims,
      minHeight: heightDims,
      maxHeight: heightDims,
      width: {
        ...widthDims
      },
      spacing: {
          '72': '18rem',
          '84': '21rem',
          '96': '24rem',
          '108': '27rem',
          '120': '30rem',
      },
      screens: {
        'print': { 'raw': 'print'},
      },
      inset: {
          '-5': '-2.5em',
          '-10': '-5em',
          '-20': '-10em',
          '-25': '-12.5em',
          '-30': '-15em',
          '-40': '-20em',
          '-50': '-25em',
          '-60': '-30em',
          '-70': '-35em',
          '0': '0em',
          '5': '2.5em',
          '10': '5em',
          '20': '10em',
          '25': '12.5em',
          '30': '15em',
          '40': '20em',
          '50': '25em',
          '60': '30em',
          '70': '35em',
          ...widthDims
      },
      margin: {
        '-5': '-2.5em',
        '96': '24rem',
        '128': '32rem',
      },
    }
  },
}