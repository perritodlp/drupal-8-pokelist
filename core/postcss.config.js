module.exports = ctx => ({
  map: !ctx.env || ctx.env !== 'production' ? { inline: false } : false,
  plugins: [
    require('postcss-custom-properties')({
      preserve: false,
      // Breaks style lint and unnecessary if preserve set to false.
      // exportTo: 'dist-css/variables.css',
      importFrom: [
        './themes/claro/css/src/base/variables.css'
      ]
    }),
    require("postcss-calc"),
    require('autoprefixer')({
      cascade: false
    }),
    require('postcss-header')({
      header: `DO NOT EDIT THIS FILE.\nSee the following change record for more information,\nhttps://www.drupal.org/node/2815083\n@preserve`,
    }),
  ]
});
