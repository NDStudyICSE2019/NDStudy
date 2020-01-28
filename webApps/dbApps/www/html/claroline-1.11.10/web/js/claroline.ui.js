/*
 * $Id: claroline.ui.js 14510 2013-08-09 08:36:48Z zefredz $
 */

Claroline.spoil = function(item) {
    $(item).parents("div").children("div.spoilerContent").toggle();
    // change link display
    $(item).parents("div").children("a.reveal").toggleClass("showSpoiler");
    $(item).parents("div").children("a.reveal").toggleClass("hideSpoiler");

    return false;
};

Claroline.getLeftMenuToggleFunction = function() {
    
    var originalLeftMargin     = $('#courseRightContent').css('margin-left');
    var originalWidth         = $('#courseLeftSidebar').css('width');
    var originalHeight         = $('#courseLeftSidebar').css('height');
    
    return function() {
        
        $('#courseToolListBlock').toggle();
        
        if ( $('#courseRightContent').css('margin-left') == originalLeftMargin ) {
            $('#courseLeftSidebar')
                .css('width', 0)
                .css('height', originalHeight)
            $('#courseRightContent').css('margin-left', 0);
            $('#toggleLeftMenu').removeClass('hide').addClass('show');
        }
        else {
            $('#courseRightContent').css('margin-left', originalLeftMargin );
            $('#courseLeftSidebar')
            .css('width', originalWidth );
            $('#toggleLeftMenu').removeClass('show').addClass('hide');
        }
        
        return false;
    }
}


/*
 * Markup should be something like 
 * <div ... class="collapsible">
 *     <a ... class="doCollapse" />
 *     <div ... class="collapsible-wrapped" /></div> 
 */
expand = function(collapsible) {
    $(collapsible).removeClass('collapsed');
    
    $(".collapsible-wrapper",collapsible).slideDown({
          duration: 'fast',
          easing: 'linear',
          complete: function() {
            collapseScrollIntoView(this.parentNode);
            this.parentNode.animating = false;
          },
          step: function() {
            // Scroll the fieldset into view
            collapseScrollIntoView(this.parentNode);
          }
        });
}

collapse = function(collapsible) {
    $(collapsible).addClass('collapsed');
    $(".collapsible-wrapper",collapsible).slideUp("fast");
}

registerCollapseBehavior = function() {
    $(".collapsed .collapsible-wrapper").hide();
    
    $(".collapsible a.doCollapse").click(function(){
        var collapsible = $(this).parents('.collapsible:first')[0];
        
        if ($(collapsible).is('.collapsed')) {
        
            expand(collapsible);
        
        }
        else {
        
            collapse(collapsible);
        
        }
        
        return false;
    });
    
    $(".expand-all").click(function(){
        $(".collapsible").each(function(){
            
            expand($(this));
            
        });
        
        return false;
    });
    
    $(".collapse-all").click(function(){
        $(".collapsible").each(function(){
            
            collapse($(this));
            
        });
        
        return false;
    });
};


/**
 * Scroll a given fieldset into view as much as possible.
 * This function is part of the Drupal js library.
 */
collapseScrollIntoView = function (node) {
  var h = self.innerHeight || document.documentElement.clientHeight || $('body')[0].clientHeight || 0;
  var offset = self.pageYOffset || document.documentElement.scrollTop || $('body')[0].scrollTop || 0;
  var posY = $(node).offset().top;
  var fudge = 55;
  
  if (posY + node.offsetHeight + fudge > h + offset) {

    if (node.offsetHeight > h) {
      window.scrollTo(0, posY);
    } else {
      window.scrollTo(0, posY + node.offsetHeight - h + fudge);
    }
  }
};

$(document).ready(function(){
    
    /**
     * Handle collapsible elements
     */
    registerCollapseBehavior();
    
    // ajax loader
    $("#loading").hide();
    
    $(document).ajaxStart(function(){
        $("#loading").show();
    });
    
    $(document).ajaxStop(function(){
        $("#loading").hide();
    });
    
    // multiple select
    $('.msadd').click(function() {
        return !$('#mslist1 option:selected').remove().appendTo('#mslist2');
    });
    
    $('.msremove').click(function() {
        return !$('#mslist2 option:selected').remove().appendTo('#mslist1');
    });
    
    $('.msform').submit(function() {
        $('#mslist1 option').each(function(i) {
            $(this).attr("selected", "selected");
        });
    });
    
    /*
     * IE8 does not support input[type=button] inside an anchor so we "need" to 
     * add this workaround (thanks to our XP and Vista users...)
     */
    $("a input[type=button]").each(function() {
        $(this).click(function() { 
            location.href=$(this).closest("a").attr("href");
        });
    });
    
    if ( $('#toggleLeftMenu') ) {
        
        $('#toggleLeftMenu').click( 
            Claroline.getLeftMenuToggleFunction()
        );
    }
    
    /**
     * Open all links with relation external in new window/tab
     */
    $('a[rel="external"]').click( function() {
        window.open( $(this).attr('href') );
        return false;
    });
    
    /**
     * Manage the qtips.  Simply add a CSS class "qtip" to an <img> or a 
     * <a> tag to add a qtip on it, displaying the "title" or "alt" (in that order)
     * value on mouseover.
     * If you deserve other renders for specifi uses of qtips, write another 
     * js cript dedicated to this use, and use a class like "qtip-custom" to 
     * refer to it.
     */
    $(".qtip").each(function()
    {
        var qtipContent = '';
        
        if ($(this).attr("title") != '')
        {
            qtipContent = $(this).attr("title");
        }
        else if ($(this).attr("alt") != '')
        {
            qtipContent = $(this).attr("alt");
        }
        
        if ( $(this).qtip && qtipContent != '')
        {
            $(this).qtip({
                content: qtipContent,
                
                show: "mouseover",
                hide: "mouseout",
                position: {
                    corner: {
                     target: "topMiddle",
                     tooltip: "bottomLeft"
                    }
                },
                
                style: {
                    width: "auto",
                    padding: 5,
                    background: "#CCDDEE",
                    color: "black",
                    fontSize: "0.9em",
                    textAlign: "center",
                    border: {
                        width: 7,
                        radius: 5,
                        color: "#CCDDEE"
                    },
                    tip: "bottomLeft"
               }
            });
        }
    });
});