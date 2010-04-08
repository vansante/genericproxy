<script type="text/javascript">
    gp.services.httpd.clickHandler = function() {
        gp.services.httpd.load();
    };

    //XML Module: Httpd
    gp.services.httpd.load = function() {
        gp.data.httpd = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/httpd.xml',
            module: 'Httpd',
            page: 'getconfig',
            error_element: $('#services_httpd_form_error'),
            content_id: 'cp_services_httpd_httpd',
            successFn: function(json) {
                gp.data.httpd = json.httpd;
                gp.services.httpd.loadForm();
            }
        });
    };

    gp.services.httpd.loadForm = function() {
        var data = gp.data.httpd;

        gp.resetForm('services_httpd_form');

        if (data.protocol.toLowerCase() == 'http') {
            $('#services_httpd_protocol_http').attr('checked', 'checked');
            $('#services_httpd_certificate').attr('disabled', 'disabled');
            $('#services_httpd_privatekey').attr('disabled', 'disabled');
        } else {
            $('#services_httpd_protocol_https').attr('checked', 'checked');
            $('#services_httpd_certificate').removeAttr('disabled');
            $('#services_httpd_privatekey').removeAttr('disabled');
        }
        $('#services_httpd_port').val(data.port);
    };

    $(function(){
        $('#services_httpd_form').submit(function(){
            gp.doFormAction({
                url: 'testxml/httpd.xml',
                form_id: 'services_httpd_form',
                error_element: $('#services_httpd_form_error'),
                successFn: function(json) {
                    gp.data.httpd = json.httpd;
                    gp.services.httpd.loadForm();
                }
            });
            return false;
        });

        $('#services_httpd_certificate').attr('disabled', 'disabled');
        $('#services_httpd_privatekey').attr('disabled', 'disabled');

        $('input[name=services_httpd_protocol]').click(function() {
            if (this.value == 'http') {
                $('#services_httpd_certificate').attr('disabled', 'disabled');
                $('#services_httpd_privatekey').attr('disabled', 'disabled');
            } else {
                $('#services_httpd_certificate').removeAttr('disabled');
                $('#services_httpd_privatekey').removeAttr('disabled');
            }
        });
    });
</script>
