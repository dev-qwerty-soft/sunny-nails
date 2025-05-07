// const purgecss = require('@fullhuman/postcss-purgecss');

module.exports = () => {
  // const isLogin = false;

  return {
    plugins: [
      require('postcss-import'),
      require('autoprefixer')({ grid: true }),
      require('postcss-combine-duplicated-selectors')({ removeDuplicatedProperties: true }),
      require('postcss-preset-env')({
        stage: 0,
        autoprefixer: { grid: true },
        features: {
          'nesting-rules': false
        }
      }),
      // !isLogin ? purgecss({
      //   content: ["./**/*.php", "./src/**/*.jsx", "./src/**/*.js", "./src/**/*.tsx", "./src/**/*.ts"],
      //   defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
      //   safelist: [
      //     "active",
      //     "menu-item-object-page",
      //     "current_page_item",
      //     "swiper-slide-active",
      //     "swiper-slide-next",
      //     "swiper-slide-thumb-active",
      //     "swiper-button-disabled",
      //     "ss-wrapper",
      //     "ss-content",
      //     "rtl",
      //     "ss-scroll",
      //     "ss-hidden",
      //     "ss-container",
      //     "swiper-initialized",
      //     "swiper-horizontal",
      //     "interface-navigable-region",
      //     "interface-interface-skeleton__content",
      //     "current",
      //     "page-template-shop",
      //   ]
      // }) : false,
      require('cssnano')({
        preset: ['default', {
          discardComments: {
            removeAll: true,
          },
        }],
      }),
      require('postcss-reporter')({
        clearReportedMessages: true,
      }),
      require("postcss-sort-media-queries")
    ].filter(Boolean),
  };
}
