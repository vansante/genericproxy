<script type="text/javascript">
    gp.diagnostics.log.clickHandler = function() {
        gp.diagnostics.log.load();
    };

    gp.diagnostics.log.load = function() {
        gp.doAction({
            url: 'testxml/log_boot.xml',
            module: 'Diagnostics',
            page: 'getbootlog',
            error_element: $('#diagnostics_log_boot_error'),
            successFn: function(json) {
                $('#diagnostics_log_boot').html('<pre>'+json.bootlog+'</pre>');
            }
        });

        gp.doAction({
            url: 'testxml/log_httpd.xml',
            module: 'Diagnostics',
            page: 'gethttpdlog',
            error_element: $('#diagnostics_log_httpd_error'),
            successFn: function(json) {
                $('#diagnostics_log_httpd').html('<pre>'+json.httpdlog+'</pre>');
            }
        });

        gp.doAction({
            url: 'testxml/log_browser.xml',
            module: 'Diagnostics',
            page: 'getbrowserlog',
            error_element: $('#diagnostics_log_browser_error'),
            successFn: function(json) {
                $('#diagnostics_log_browser').html('<pre>'+json.browserlog+'</pre>');
            }
        });
    };
</script>