<script type="text/javascript">
    gp.status.traffic.clickHandler = function() {
        gp.status.traffic.load('wan');
        gp.status.traffic.load('lan');
        gp.status.traffic.load('ext');
    };

    gp.status.traffic.load = function(iface) {
        var time = new Date().getTime();
        
        $('#status_traffic_'+iface+'_daily').html('<img src="images/mrtg/'+iface+'-day.png?t='+time+'" id="status_traffic_'+iface+'_daily_img" alt="Daily"/>');
        $('#status_traffic_'+iface+'_weekly').html('<img src="images/mrtg/'+iface+'-week.png?t='+time+'" id="status_traffic_'+iface+'_weekly_img" alt="Weekly"/>');
        $('#status_traffic_'+iface+'_monthly').html('<img src="images/mrtg/'+iface+'-month.png?t='+time+'" id="status_traffic_'+iface+'_monthly_img" alt="Monthly"/>');
        $('#status_traffic_'+iface+'_yearly').html('<img src="images/mrtg/'+iface+'-year.png?t='+time+'" id="status_traffic_'+iface+'_yearly_img" alt="Yearly"/>');
    };

    gp.status.traffic.reload = function(iface) {
        var time = new Date().getTime();

        $('#status_traffic_'+iface+'_daily_img').attr('src', 'images/mrtg/'+iface+'-day.png?t='+time);
        $('#status_traffic_'+iface+'_weekly_img').attr('src', 'images/mrtg/'+iface+'-week.png?t='+time);
        $('#status_traffic_'+iface+'_monthly_img').attr('src', 'images/mrtg/'+iface+'-month.png?t='+time);
        $('#status_traffic_'+iface+'_yearly_img').attr('src', 'images/mrtg/'+iface+'-year.png?t='+time);
    };

    $(function() {
        $('.status_traffic_refresh_link').click(function() {
            gp.status.traffic.reload($(this).attr('rel'));
            return false;
        });
    });
</script>