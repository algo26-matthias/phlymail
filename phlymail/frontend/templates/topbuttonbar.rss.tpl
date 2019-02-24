<script type="text/javascript">
//<![CDATA[
rss_customheight_preview = <!-- START customheight -->{height} + <!-- END customheight -->0;
<!-- START has_new_feed -->
pm_menu_additem
        ('settings'
        ,'{theme_path}/icons/rss_men.png'
        ,'%j%RSSFeedSubscriptions%'
        ,'{PHP_SELF}?l=subscriptions&h={handler}&{passthrough}'
        ,400
        ,600
        );<!-- END has_new_feed --><!-- START has_exchange -->
pm_menu_additem
        ('exchange'
        ,'{theme_path}/icons/rss_men.png'
        ,'%j%RSSExchangeFeeds%'
        ,'{PHP_SELF}?l=exchange&h={handler}&{passthrough}'
        ,500
        ,500
        );<!-- END has_exchange -->
// ]]>
</script>