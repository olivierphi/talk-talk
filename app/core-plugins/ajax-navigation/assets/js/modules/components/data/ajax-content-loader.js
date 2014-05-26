define(function (require, exports, module) {
  "use strict";

  var defineComponent = require("flight").component;
  var varsRegistry = require("app-modules/core/vars-registry");
  var withAjax = require("app-modules/utils/mixins/data/with-ajax");
  var withDataCache = require("app-modules/utils/mixins/data/with-data-cache");
  var withUrlNormalization = require("app-modules/utils/mixins/data/with-url-normalization");
  var withHttpStatusManagement = require("app-modules/utils/mixins/data/with-http-status-management");
  var withAlertsCapabilities = require("app-modules/utils/mixins/ui/with-alerts-capabilities");
  var withDateUtils = require("app-modules/utils/mixins/data/with-date-utils");
  var purl = require("purl");
  var $ = require("jquery");
  var logger = require("logger");

  var myDebug = !false;

  // Exports: component definition
  module.exports = defineComponent(
    ajaxContentLoader,
    withAjax, withDataCache, withUrlNormalization,
    withHttpStatusManagement, withAlertsCapabilities, withDateUtils
  );

  var AJAX_CONTENT_CACHE_KEYS_PREFIX = "ajax-content-data---";

  myDebug && logger.debug(module.id, "Component on the bridge, captain!");

  function ajaxContentLoader() {

    this.defaultAttrs({
    });

    this.onAjaxContentLoadRequest = function (ev, data) {
      this.getAjaxContent(data.url, data.target);
    };

    this.onCheckForAjaxDataInstructionsInContentRequest = function(ev, data) {
      this.checkForAjaxDataInstructionsInContent(data.url, data.target);
    };

    this.onAjaxContentCacheClearingRequest = function(ev, data) {
      this.clearPreviousSessionCache();
    };

    this.getAjaxContent = function (contentUrl, targetSelector) {
      var url = this.normalizeUrl(contentUrl);
      var contentFromCache = this.getCacheData(this.getAjaxContentCacheKey(url));
      var hasContentFromCache = (null !== contentFromCache);
      myDebug && logger.debug(module.id, "We "+(hasContentFromCache ? "do" : "don't")+" have content in cache for this URL.");
      if (!hasContentFromCache) {
        // No content in the data store for this URL:
        // --> let's load it!
        this.loadAjaxContent(contentUrl, targetSelector);
      } else {
        // Easy-peasy, we already have this content in the data store
        this.displayAjaxContent(contentUrl, targetSelector, contentFromCache);
      }
    };

    this.loadAjaxContent = function (contentUrl, targetSelector) {
      var url = this.normalizeUrl(contentUrl);
      this.trigger("ajaxContentLoadingStart", {
        url: url,
        target: targetSelector
      });

      this.ajax({
        url: url,
        dataType: "text"
      }).then(
        _.partial(this.onAjaxLoadSuccess, url, targetSelector, new Date),
        _.partial(this.onAjaxLoadError, url, targetSelector)
      );
    };

    this.displayAjaxContent = function(url, targetSelector, htmlContent) {
      var eventPayload = {
        fromUrl: url,
        target: targetSelector,
        content: htmlContent
      };
      this.trigger("uiNeedsContentUpdate", eventPayload);
    };

    this.getAjaxContentCacheKey = function(url) {
      return AJAX_CONTENT_CACHE_KEYS_PREFIX + url;
    };

    this.getCachedAjaxContent = function(url) {
      return this.getCacheData(this.getAjaxContentCacheKey(url));
    };

    this.onAjaxLoadSuccess = function (url, targetSelector, loadingStartDate, content) {
      this.trigger("ajaxContentLoadingDone", {
        url: url,
        target: targetSelector,
        duration: this.getDuration(loadingStartDate)
      });
      this.displayAjaxContent(url, targetSelector, content);
      this.checkForAjaxDataInstructionsInContent(url, targetSelector);
    };

    this.onAjaxLoadError = function (url, targetSelector, jqXHR, textStatus, err) {
      myDebug && logger.debug(module.id, "Ajax link '" + url + "' loading failed!");
      this.trigger("ajaxContentLoadingError", {
        url: url,
        target: targetSelector
      });
      this.displayAlert(
        "core-plugins.ajax-navigation.alerts.loading-error",
        {"%contentUrl%": url},
        "error"
      );
    };

    this.clearPreviousSessionCache = function () {
      this.clearCacheDataForPrefix(AJAX_CONTENT_CACHE_KEYS_PREFIX);
    };

    this.checkForAjaxDataInstructionsInContent = function(contentSourceUrl, targetSelector) {

      if (this.isCurrentPageAnError()) {
        // Don't handle anything if the current page is a error page
        return;
      }

      if (null !== this.getCachedAjaxContent(contentSourceUrl)) {
        // We already have cached content for this URL. We have to wait for its expiration...
        return;
      }

      var $target = $(targetSelector);

      var $ajaxLoadingDataPlaceholder = $target.find('.ajax-loading-data');
      if ($ajaxLoadingDataPlaceholder.length === 0)
        return;//no loading data cache

      var ajaxCacheInstructions = $ajaxLoadingDataPlaceholder.data("ajax-cache");
      myDebug && logger.debug(module.id, "Ajax loading data=", ajaxCacheInstructions);

      if (!!ajaxCacheInstructions.duration) {

        myDebug && logger.debug(module.id, "Ajax content will be kept in cache " +
          "during " + ajaxCacheInstructions.duration + " seconds.");

        this.setCacheData(
          this.getAjaxContentCacheKey(contentSourceUrl),
          $target.html(),
          ajaxCacheInstructions.duration
        );
      }
    };

    // Component initialization
    this.after("initialize", function() {
      // Let"s start with an empty data cache for the moment...
      this.clearPreviousSessionCache();
      // All right, let's track some events, now...
      this.on("uiNeedsContentAjaxLoading", this.onAjaxContentLoadRequest);
      this.on("uiNeedsContentAjaxInstructionsCheck", this.onCheckForAjaxDataInstructionsInContentRequest);
      this.on("uiNeedsContentAjaxLoadingCacheClearing", this.onAjaxContentCacheClearingRequest);
    });
  }

});