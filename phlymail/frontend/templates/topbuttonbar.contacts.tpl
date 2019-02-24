<script type="text/javascript">
// <![CDATA[
contacts_customheight_preview = <!-- START customheight -->{height} + <!-- END customheight -->0;
<!-- START has_new_contact -->
pm_menu_additem
        ('new'
        ,'{theme_path}/icons/contact_men.gif'
        ,'{msg_newcontact}'
        ,'{PHP_SELF}?l=edit_contact&h=contacts&{passthrough}'
        ,510
        ,470
        );<!-- END has_new_contact --><!-- START has_exchange -->
pm_menu_additem
        ('exchange'
        ,'{theme_path}/icons/contact_men.gif'
        ,'{msg_setup_contacts}'
        ,'{PHP_SELF}?l=exchange&h=contacts&{passthrough}'
        ,500
        ,550
        );<!-- END has_exchange -->

pm_menu_additem
        ('settings'
        ,'{theme_path}/icons/contact_men.gif'
        ,'{msg_setup_contacts}'
        ,'{PHP_SELF}?l=setup&h=contacts&{passthrough}'
        ,580
        ,550
        );
pm_menu_additem
        ('settings'
        ,'{theme_path}/icons/contact_men.gif'
        ,'{msg_edit_vcf}'
        ,'{PHP_SELF}?l=edit_vcf&h=contacts&{passthrough}'
        ,510
        ,470
        );

// ]]>
</script>