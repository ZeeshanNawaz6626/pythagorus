(function($) {
    jQuery(document).ready(function() {
      jQuery('.layer').each(function() {
        var $this = $(this),
          limit = 50,
          $star = [];
  
        while (limit) {
          $star = jQuery('<span/>').addClass('star');
          $star.html('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M10.744,5.372C7.777,5.372,5.372,2.967,5.372,0c0,2.967-2.405,5.372-5.372,5.372c2.967,0,5.372,2.405,5.372,5.372 C5.372,7.777,7.777,5.372,10.744,5.372z"/></svg>');
          $star.css({
            left: randomInt(1, 100) + '%',
            top: randomInt(1, 100) + '%',
            transform: 'scale(' + randomNum(0, 1) + ')',
            opacity: randomNum(0.6, 1)
          });
          $this.append($star);
          limit--;
        }
      });
    });
  
    function randomInt(min, max) {
      return Math.floor((Math.random() * max) + min);
    }
    
    function randomNum(min, max) {
      return Math.max((Math.random() * max), min).toFixed(1);
    }
})(jQuery);