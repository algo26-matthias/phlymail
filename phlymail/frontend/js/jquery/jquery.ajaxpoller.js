/**
 * Original by Eric Hynds: http://www.erichynds.com/javascript/a-recursive-settimeout-pattern/
 *
 * Slightly adapted to have more than one poller with individual URLs
 * @copyright 2011 phlyLabs, Berlin
 * @version 0.0.1 2011-04-21
 */

var ajaxPoller = {
   // number of failed requests
   failed: 0,
   // starting interval - 5 seconds
   interval: 5000,
   // URI to poll (set through init)
   polluri: null,

   // kicks off the setTimeout
   init: function(inituri) {
       if (inituri) this.polluri = inituri;
       setTimeout(
           $.proxy(this.getData, this), // ensures 'this' is the poller obj inside getData, not the window object
           this.interval
       );
   },

   // get AJAX data + respond to it
   getData: function(){
       var self = this;

       $.ajax({
           url: self.polluri,
           success: function( response ){

               // what you consider valid is totally up to you
               if( response === "failure" ){
                   self.errorHandler();
               } else {
                   // recurse on success
                   self.init();
               }
           },

           // 'this' inside the handler won't be this poller object
           // unless we proxy it.  you could also set the 'context'
           // property of $.ajax.
           error: $.proxy(self.errorHandler, self)
       });
   },

   // handle errors
   errorHandler: function(){
       if( ++this.failed < 10 ){

           // give the server some breathing room by
           // increasing the interval
          this.interval += 1000;

          // recurse
          this.init();
       }
   }
};