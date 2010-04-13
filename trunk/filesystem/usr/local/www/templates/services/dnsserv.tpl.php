<script type="text/javascript">
    gp.services.dnsserv.clickHandler = function() {
        gp.services.dnsserv.load();
    };

    //XML Module: Httpd
    gp.services.dnsserv.load = function() {
        gp.data.dnsserver = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/dnsserver.xml',
            module: 'MaraDNS',
            page: 'getconfig',
            error_element: $('#services_dnsserv_form_error'),
            content_id: 'cp_services_dnsserv_dnsserv',
            successFn: function(json) {
                gp.data.dnsserver = json.maradns;
                gp.services.dnsserv.loadForm();
            }
        });
    };

    gp.services.dnsserv.loadForm = function() {
        var data = gp.data.dnsserver;

        gp.resetForm('services_dnsserv_form');

        $('#services_dnsserv_server').val(data.server);
        $('#services_dnsserv_zone').val(data.zone);
    };

    $(function(){
        $('#services_dnsserv_form').submit(function(){
            gp.doFormAction({
                url: 'testxml/dnsserver.xml',
                form_id: 'services_dnsserv_form',
                error_element: $('#services_dnsserv_form_error'),
                successFn: function(json) {
                    gp.data.dnsserver = json.maradns;
                    gp.services.dnsserv.loadForm();
                }
            });
            return false;
        });

        $('#services_dnsserver_fetchzone').click(function(){
            gp.doAction({
                url: 'testxml/reply.xml',
                module: 'MaraDNS',
                page: 'fetchzone',
                error_element: $('#services_dnsserv_form_error'),
                content_id: 'cp_services_dnsserv_dnsserv',
                successFn: function(json) {
                    gp.alert("Success", "DNS zone was fetched successfully.");
                }
            });
            return false;
        });
    });
</script>
