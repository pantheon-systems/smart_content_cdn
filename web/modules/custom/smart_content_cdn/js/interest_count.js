/**
 * @file
 * Count interests and set cookie for interest header.
 */
 (function ($, Drupal, cookies) {
  Drupal.behaviors.interestCount = {
    attach: function (context, settings){
      // How many times should a tag be visited before adding to interest header.
      const popularityCount = ('interest_count' in settings && 'interest_threshold' in settings.interest_count) ? settings.interest_count.interest_threshold : 3;
      const freeArticles = ('interest_count' in settings && 'view_threshold' in settings.interest_count) ? settings.interest_count.view_threshold : 3;
      /**
       * Update tagsCount with current node tags.
       */
      function updateTagsCount(nodeTags, tagsCount) {
        // Loop through current node tags.
        nodeTags.forEach(tag => {
          // If tag already exists in tagsCount, increment.
          if (tag in tagsCount) {
            tagsCount[tag]++;
          }
          // Otherwise, count as 1.
          else {
            tagsCount[tag] = 1;
          }
        });

        return tagsCount;
      }

      /**
       * Filter out the most popular tags.
       */
      function getInterestTags(tagsCount) {
        // Find the highest count among the tags.
        var maxCount = 0;
        Object.keys(tagsCount).forEach(tag => {
          // If tag's count is above popularityCount and other tag counts.
          if (tagsCount[tag] >= popularityCount && tagsCount[tag] > maxCount) {
            maxCount = tagsCount[tag];
          }
        });

        // If there are valid tags with a maxCount.
        if (maxCount > 0) {
          // Convert tagsCount to array.
          let tagsCountArray = Object.entries(tagsCount);
          // Retrieve all tags with count equal to maxCount.
          tagsCountArray = tagsCountArray.filter(([key, value]) => value === maxCount);

          // Convert array back to object.
          return Object.fromEntries(tagsCountArray);
        }

        // If no tag has a sufficient maxCount, return an empty object.
        return {};
      };

      // Main interest count code to run once.
      $('body', context).once('interestCount').each(function() {
        // Get current node tags.
        const nodeTags = ('interest_count' in settings && 'tags' in settings.interest_count) ? settings.interest_count.tags : null;

        if (nodeTags) {
          // Create LocalStorage instance.
          var storage = new LocalStorage();

          // Get tagsCount from localStorage if it exists.
          let tagsCount = storage.getStorage(false);

          // Update tagsCount with current node tags.
          tagsCount = updateTagsCount(nodeTags, tagsCount);

          // Save updated counts to localStorage.
          storage.setStorage(tagsCount, false);

          // Filter most popular tags.
          let interestTagsCount = getInterestTags(tagsCount);

          // Get array of popular tag tids.
          let interestTags = Object.keys(interestTagsCount);

          if (interestTags.length > 0) {
            // Set interest cookie with popular tags, separated by |.
            cookies.set('interest', interestTags.join('|'));
          }
        }
      });
      $('body', context).once('viewCount').each(function(){
          // Create LocalStorage instance.
          var storage = new LocalStorage();
          // Get viewCount from localStorage if it exists.
          let viewCount = storage.getStorage(true);
          if (viewCount > 0) {
            viewCount++;
          }
          else {
            viewCount = 1;
          }
          // Save updated counts to localStorage.
          storage.setStorage(viewCount, true);
          if (viewCount >= freeArticles) {
            // Set role cookie.
            cookies.set('role', 'anonymous');
          }
      })
    }
  }

  /**
     * Class to handle localStorage.
     */
  class LocalStorage {

    /**
     * Implements constructor().
     */
    constructor() {
      // localStorage key.
      this.key = 'smart_content_cdn.interest';
      this.view_key = 'smart_content_cdn.view';
    }

    /**
     * Get value in localStorage.
     */
    getStorage(view) {
      let item;
      if (view) {
        item = localStorage.getItem(this.view_key);
      }
      else {
        item = localStorage.getItem(this.key);
      }

      return item ? JSON.parse(item) : {};
    }

    /**
     * Set value in localStorage.
     */
    setStorage(value, view) {
      if (view) {
        localStorage.setItem(this.view_key, JSON.stringify(value));
      }
      else {
        localStorage.setItem(this.key, JSON.stringify(value));
      }

    }
  }

})(jQuery, Drupal, window.Cookies);
