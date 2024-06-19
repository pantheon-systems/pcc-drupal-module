(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.preview_refresh = {
    attach: function (context, settings) {
      let interval = 2000;
      const previwView = '.views-pcc-live-preview';
      const livePreviewText = document.querySelector('.preview-status');
      const livePreviewTimeRemaining = settings.realtime_preview.previewActiveUntil - Date.now();
      // Live preview is active for 600000 mili-seconds.
      // Auto refresh the views for 200000 mili-seconds.
      if ($(previwView).length > 0 && livePreviewTimeRemaining > 400000) {
        if (settings.realtime_preview.previewActiveUntil) {
          livePreviewText.innerText = "On"
          clearTimeout(settings.realtime_preview.previewActiveUntil);
        }
        settings.realtime_preview.previewActiveUntil = setTimeout(
          function () {
            Drupal.behaviors.preview_refresh.refresh(previwView)
          }, interval
        );
      }

      // Hides the throbber on refresh for views that use autorefresh.
      for(let i in Drupal.views.instances) {
        let view_set = Drupal.views.instances[i].settings;
        if(view_set.view_name === "pantheon_cloud_api" && view_set.view_display_id === "realtime_preview") {
          if(Drupal.views.instances[i].hasOwnProperty('refreshViewAjax')) {
            Drupal.views.instances[i].refreshViewAjax.progress = {type: 'none'};
          }
        }
      }
    },
    refresh: function (previwView) {
      $(previwView).trigger('RefreshView');
    }
  }
})(jQuery, Drupal, drupalSettings);
