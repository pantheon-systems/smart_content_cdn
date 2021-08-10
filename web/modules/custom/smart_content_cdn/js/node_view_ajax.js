/**
 * @file
 * Call ajax callback on node view.
 */

(function ($, Drupal) {
  Drupal.behaviors.nodeView = {
    attach: function (context, settings){
      $('body', context).once('nodeViewBehavior').each(function() {
        if ('smart_content_cdn' in settings && 'nid' in settings.smart_content_cdn) {
          let nid = settings.smart_content_cdn.nid;
          let target = Drupal.url('smart_content_cdn/interest_count/' + nid);
          Drupal.ajax({url: target}).execute();
        }
      });
    }
  }
})(jQuery, Drupal);
