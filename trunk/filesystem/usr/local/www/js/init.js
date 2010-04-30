/***************************************
****************************************
 * init.js
 *
 * @desc Initialisatie van javascript
 */

/* Initialize tabset */
$(function() {
    $('.tabset').tabs();

    // Initialize the accordion for the menu
    $('#menu').accordion({
        autoHeight: false,
        collapsible: true,
        active: false
    });

    //Initilize the click handlers to make the menu work.
    $('.menu_submenu li a').click(function() {
        //Open het juiste paneel
        $('.module').hide();
        $('.page').hide();
        $('a.active').removeClass('active');
        var contentpart = $('#cp_'+$(this).attr('id'));
        $(this).addClass('active');
        //Initializeer een hook methode die in de pagina template wordt overschreven
        var url_str = $(this).attr('rel').split('->');

        //Toon het paneel
        contentpart.show();
        contentpart.parent().show();

        //Module = urlStr[0], Pagina = urlStr[1]
        if (gp[url_str[0]] && gp[url_str[0]][url_str[1]] && gp[url_str[0]][url_str[1]].clickHandler) {
            gp[url_str[0]][url_str[1]].clickHandler();
        }
    });

    // Initialize a page when it's opened by an anchor
    var hash = self.document.location.hash;
    if (hash.length > 2) {
        var anchor = $(self.document.location.hash);
        if (anchor.attr('rel')) {
            var contentpart = $('#cp_'+anchor.attr('id'));
            $(anchor).addClass('active');
            //Initializeer een hook methode die in de pagina template wordt overschreven
            var urlStr = anchor.attr('rel').split('->');

            //Module = urlStr[0], Pagina = urlStr[1]
            if (gp[urlStr[0]] && gp[urlStr[0]][urlStr[1]] && gp[urlStr[0]][urlStr[1]].clickHandler) {
                gp[urlStr[0]][urlStr[1]].clickHandler();
            }

            //Toon het paneel
            contentpart.show();
            contentpart.parent().show();

            $('#menu').accordion('activate', '#'+urlStr[0]);
            
        } else if (gp.status) {
            gp.showHomepage();
        }
    } else if (gp.status) {
        gp.showHomepage();
    }

    // Build help icons
    $('label').each(function() {
        var helpElement = $('#help_'+$(this).attr('for'));
        //Check if help text extists
        if (helpElement.length == 1) {
            //Give the help a header
            helpElement.prepend('<h3>Help</h3>').addClass('hidden');

            //DT to display help in
            $('<dt />')
                .insertBefore($(this).parent())
                .css('width', '100%')
                .css('padding', '0')
                .append(helpElement);

            var helpHoverElement = $('<div class="help_hover" id="'+helpElement.attr('id')+'_hover">'+helpElement.html()+'</div>');
            helpHoverElement.insertAfter($('#help_hover_pool'));

            //Build the link to open/close help
            var helpLink = $('<a href="#" class="open_help"/>');
            helpLink.click(function() {
                helpHoverElement.hide();
                helpElement.slideToggle();
                helpElement.toggleClass('hidden');
                $(this).parent().toggleClass('noborder');
            });
            helpLink.mouseover(function() {
                if (helpElement.hasClass('hidden')) {
                    helpHoverElement.css('top', (helpLink.offset().top + 10) + 'px');
                    helpHoverElement.css('left', (helpLink.offset().left + 30) + 'px');
                    helpHoverElement.fadeIn(250);
                }
            });
            helpLink.mouseout(function() {
                helpHoverElement.fadeOut(250);
            });
            helpLink.insertBefore($(this));
        }
    });

    $('.open_all_help').click(function() {
        if( $('#'+this.rel+' .help.hidden').size() == 0 ) {
            $('#'+this.rel+' .help').slideUp().addClass('hidden').parent().next().removeClass('noborder');
        } else {
            $('#'+this.rel+' .help').slideDown().removeClass('hidden').parent().next().addClass('noborder');
        }
    });

    $('#logout_link').click(function(){
        gp.doAction({
            page: 'logout',
            successFn: function(json) {
                window.location.reload(true);
            }
        });
        return false;
    })
    // Hide arrow for logout option...
    .prev('.ui-icon-triangle-1-e').hide();
    
    if (gp.system && gp.system.update && gp.system.update.auto) {
        gp.system.update.auto.checkUpdates(true);
    }
});

gp.showHomepage = function() {
    // Show default homepage: System status
    var cp = $('#cp_status_system');
    cp.show();
    cp.parent().show();
    $('#status_system').addClass('active');
    $('#menu').accordion('activate' , '#status');
    gp.status.system.clickHandler();
};

/*
 * Argument: one object with the following properties:
 *  - module: modulename (optional)
 *  - page: pagename (optional)
 *  - params: extra post parameters in object form, or querystring(does not work with file) (optional)
 *  - error_element: the element the error appears in (optional)
 *  - content_id: the id of the element the ajax loader should appear in (optional)
 *  - successFn: function that gets called with the json as parameter when the request was successful (optional)
 *  - errorFn:  function that gets called when the request fails (optional)
 *  - url: Can be overridden for testing purposes, default 'ajaxserver.php'
 */
gp.doAction = function(opts) {
    if (opts.error_element) {
        if ($.isArray(opts.error_element)) {
            $.each(opts.error_element, function(i, el) {
                el.slideUp(150);
            });
        } else {
            opts.error_element.slideUp(150);
        }
    }
    if (opts.content_id) {
        gp.showAjaxLoader(opts.content_id);
    }
    var postFields = {};
    if (opts.module) {
        postFields.module = opts.module;
    }
    if (opts.page) {
        postFields.page = opts.page;
    }
    if (opts.params) {
        for (var field in opts.params) {
            postFields[field] = opts.params[field];
        }
    }
    $.ajax({
        url: gp.debug && opts.url ? opts.url : 'ajaxserver.php',
        type: 'POST',
        data: postFields,
        error: function(request, textStatus, error) {
            if (opts.content_id) {
                gp.hideAjaxLoader(opts.content_id);
            }
            gp.handleRequestError(request, textStatus, opts.error_element, opts.errorFn);
        },
        success: function(data, textStatus, request) {
            if (opts.content_id) {
                gp.hideAjaxLoader(opts.content_id);
            }
            $('#'+opts.form_id+' input[type=submit]').removeAttr('disabled');
            gp.processReply(data, opts.error_element, opts.successFn, opts.errorFn);
        }
    });
};

/*
 * Argument: one object with the following properties:
 *  - form_id: id of the form
 *  - error_element: the element the error appears in (optional)
 *  - successFn: function that gets called with the json as parameter when the request was successful (optional)
 *  - errorFn:  function that gets called when the request fails (optional)
 *  - url: Can be overridden for testing purposes, default 'ajaxserver.php'
 */
gp.doFormAction = function(opts) {
    $('#'+opts.form_id+' input[type=submit]').attr('disabled', 'disabled');
    gp.showAjaxLoader(opts.form_id);
    if (opts.error_element) {
        if ($.isArray(opts.error_element)) {
            $.each(opts.error_element, function(i, el) {
                el.slideUp(150);
            });
        } else {
            opts.error_element.slideUp(150);
        }
    }
    $('#'+opts.form_id).ajaxSubmit({
        url: gp.debug && opts.url ? opts.url : 'ajaxserver.php',
        type: 'POST',
        dataType: 'xml',
        clearForm: false,
        resetForm: false,
        error: function(request, textStatus, error) {
            gp.hideAjaxLoader(opts.form_id);
            $('#'+opts.form_id+' input[type=submit]').removeAttr('disabled');
            gp.handleRequestError(request, textStatus, opts.error_element, opts.errorFn);
        },
        success: function(data, textStatus, request) {
            gp.hideAjaxLoader(opts.form_id);
            $('#'+opts.form_id+' input[type=submit]').removeAttr('disabled');
            gp.processReply(data, opts.error_element, opts.successFn, opts.errorFn);
        }
    });
}
gp.handleRequestError = function(request, textStatus, error_element, errorFn) {
    switch(textStatus) {
        case 'parsererror':
            if (request.responseText) {
                gp.displayError('Server response:<br><pre><code class="parse-error-output">'+$('<div/>').text(request.responseText).html()+'</code></pre>', 'Invalid response', error_element);
            } else {
                gp.displayError('The server returned an empty response', 'Invalid response', error_element);
            }
            break;
        case 'timeout':
            gp.displayError('The page request timed out.', 'Request time out', error_element);
        default:
            gp.displayError('The server was unreachable', 'Server unreachable', error_element);
            break;
    }
    if (errorFn) {
        errorFn();
    }
};
gp.displayError = function(message, title, error_element) {
    var str = '';
    if (error_element) {
        if ($.isArray(error_element)) {
            $.each(error_element, function(i, el) {
                gp.displayError(message, title, el);
            });
            return;
        }
        error_element.slideUp(350);
        if (title) {
            str += '<h3 class="error">'+title+'</h3>';
        }
        error_element.html(str+'<p class="error">'+message+'</p>');
        error_element.slideDown(450);
    } else {
        gp.alert(title, message);
    }
};
gp.processReply = function(data, error_element, successFn, errorFn) {
    var json = $.xml2json(data);

    if (json && json.action && json.action.toLowerCase() == 'ok') {
        if (json.message) {
            gp.alert('Server notice', json.message);
        }
        if (successFn) {
            successFn(json);
        }
        return true;
    } else if (json) {
        if (json.action && json.action.toLowerCase() == 'login-error') {
            gp.alert('Session timeout', json.message+'<br>You will be redirected to the login page.');
            window.setTimeout("window.location.reload(true);", 3000);
            if (errorFn) {
                errorFn();
            }
            return false;
        }
        if (json.message) {
            if ($.isArray(json.message)) {
                var msg = '<ul>'
                $.each(json.message, function(i, message){
                    msg += '<li>'+message.text[0]+'</li>';
                });
                msg += '</ul>';
                gp.displayError(msg, 'An exception occurred', error_element);
            } else {
                gp.displayError(json.message.text[0], 'An exception occurred', error_element);
            }
        }
        if (json.formfield) {
            if ($.isArray(json.formfield)) {
                $.each(json.formfield, function(i, formfield){
                    gp.markFieldInvalid(formfield.id);
                });
            } else {
                gp.markFieldInvalid(json.formfield.id);
            }
        }
        if (!json.message && !json.formfield) {
            gp.displayError('<p>An unknown error occured! Action failed.</p>', 'Unknown error', error_element);
        }
    }
    if (!json) {
        gp.displayError('<p>An unknown error occured!</p><p>Server response:</p><pre><code class="parse-error-output">'+$('<div/>').text(data).html()+'</code></pre>', 'Unknown error', error_element);
    }
    if (errorFn) {
        errorFn();
    }
    return false;
};
gp.markFieldInvalid = function(field_id) {
    $('#'+field_id).addClass('formfield-error');
}
gp.resetForm = function(form_id) {
    $('#'+form_id+'_error').hide();
    $('#'+form_id+' input').each(function(i, input){
        input = $(input);
        input.removeClass('formfield-error');
        switch (input.attr('type')) {
            case 'checkbox':
                input.trigger('click');
            case 'radio':
                input.removeAttr('checked');
                break;
            case 'submit':
                input.removeAttr('disabled');
                break;
            case 'hidden':
                // Do nothing
                break;
            default:
                input.val('');
                break;
        }
    });
    $('#'+form_id+' select').each(function(i, select){
        select = $(select);
        select.removeClass('formfield-error');
        select.val('');
        select.trigger('change');
    });
};
gp.alert = function(title, message) {
    $('<div><p>'+message+'</p></div>').dialog({
        title: title,
        autoOpen: true,
        resizable: false,
        width: 300,
        minHeight: 100,
        modal: true,
        buttons: {
            "OK": function() {
                $(this).dialog("close");
            }
        }
    });
};
gp.confirm = function(title, message, successFn, successTxt, failFn, failTxt) {
    var btns = {};
    if (failTxt) {
        btns[failTxt] = function() {
            if (failFn) {
                failFn();
            }
            $(this).dialog('close');
        };
    } else {
        btns['Cancel'] = function() {
            if (failFn) {
                failFn();
            }
            $(this).dialog('close');
        };
    }
    if (successTxt) {
        btns[successTxt] = function() {
            if (successFn) {
                successFn();
            }
            $(this).dialog('close');
        };
    } else {
        btns['OK'] = function() {
            if (successFn) {
                successFn();
            }
            $(this).dialog('close');
        };
    }
    $('<div><p>'+message+'</p></div>').dialog({
        title: title,
        autoOpen: true,
        resizable: false,
        width: 400,
        minHeight: 100,
        modal: true,
        buttons: btns
    });
};

gp.rebootNotice = function(seconds) {
    if (!seconds) {
        seconds = 75;
    }
    $('<div><p>The device is rebooting, please wait. The page will refresh in <span id="reboot_countdown_timer">'+seconds+'</span> seconds.</p></div>').dialog({
        title: 'Device rebooting',
        autoOpen: true,
        resizable: false,
        width: 300,
        minHeight: 100,
        modal: true,
        closeOnEscape: false,
        beforeClose: function() {
            return false;
        }
    });
    gp.data.reboot_seconds = seconds;
    window.setInterval('gp.rebootCountDown();', 1000);
};

gp.rebootCountDown = function() {
    gp.data.reboot_seconds--;
    
    $('#reboot_countdown_timer').html(gp.data.reboot_seconds);

    if (gp.data.reboot_seconds <= 0) {
        window.location.reload(true);
    }
}

gp.showAjaxLoader = function(el_id) {
    if ($.isArray(el_id)) {
        $.each(el_id, function(i, el){
            gp.showAjaxLoader(el);
        });
        return;
    }

    var element = $('#'+el_id);
    var loader = $('#'+el_id+'_loader');
    if (!loader.length) {
        loader = $('<div class="ajax-form-loader" id="'+el_id+'_loader"><img src="images/loader.gif" alt="loader"/> Loading..</div>');
        element.append(loader);
    }
    var top = element.position().top;
    var left = element.position().left;
    if (element.hasClass('dialog')) {
        top = element.height() / 2 - (32 / 2);
        left = element.width() / 2 - (120 / 2);
    } else if (top == 0 && left == 0) {
        top = 60;
        left = 352;
    } else {
        top = top + (element.height() / 2 - (32 / 2));
        left = left + (element.width() / 2 - (120 / 2));
    }
    loader.css('top', top);
    loader.css('left', left);
    loader.show();
};

gp.hideAjaxLoader = function(el_id) {
    if ($.isArray(el_id)) {
        $.each(el_id, function(i, el){
            gp.hideAjaxLoader(el);
        });
        return;
    }

    $('#'+el_id+'_loader').hide();
};

String.prototype.trim = function() {
    return this.replace(/(^\s+|\s+$)/,'');
}

if(!Array.indexOf){
    Array.prototype.indexOf = function(obj){
        for(var i = 0; i < this.length; i++){
            if (this[i] == obj){
                return i;
            }
        }
        return -1;
    }
}