<script type="text/javascript">
    gp.services.ntp.clickHandler = function() {
        gp.services.ntp.load();
    };

    //XML Module: System
    gp.services.ntp.load = function() {
        gp.data.ntp = {};
        gp.data.ntp_timezones = {};

        //Handle XML loading
        gp.doAction({
            url: 'testxml/ntp.xml',
            module: 'System',
            page: 'getntpconfig',
            error_element: $('#services_ntp_form_error'),
            successFn: function(json) {
                gp.data.ntp = json.ntp;

                if (json.ntp.timezones.zone) {
                    var zone = json.ntp.timezones.zone;
                    if ($.isArray(zone)) {
                        $.each(zone, function(i, rule) {
                            gp.data.ntp_timezones[i] = rule;
                        });
                    } else {
                        gp.data.ntp_timezones[0] = zone;
                    }
                }
                gp.services.ntp.loadForm();
            }
        });
    };

    gp.services.ntp.loadZones = function() {
        var opts = '';
        $.each(gp.data.ntp_timezones, function(i, zone) {
            opts += '<option value="'+zone.id+'">'+zone.id+'</option>';
        });
        $('#services_ntp_timezone').html(opts);
    };

    gp.services.ntp.loadForm = function() {
        gp.services.ntp.loadZones();
        gp.resetForm('services_ntp_form');

        var data = gp.data.ntp;

        $('#services_ntp_timezone').val(data.current);
        $('#services_ntp_interval').val(data.update_interval);
        $('#services_ntp_server').val(data.server);
    };

    $(function(){
        //Handler for submitting the form
        $('#services_ntp_form').submit(function() {
            gp.doFormAction({
                url: 'testxml/ntp.xml',
                form_id: 'services_ntp_form',
                error_element: $('#services_ntp_form_error'),
                successFn: function(json) {
                    gp.data.ntp = json.ntp;
                    gp.services.ntp.loadForm();
                }
            });
            return false;
        });
    });
</script>
