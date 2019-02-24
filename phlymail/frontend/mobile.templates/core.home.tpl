<ul data-role="listview" data-inset="true" class="x32">
    <li data-role="list-divider">%h%HomeFavourites%</li><!-- START favorite -->
	<li><a href="{link}"><img src="{icon}" alt="{name}" class="ui-li-icon">{name}</a></li><!-- END favorite -->
    <li data-role="list-divider">%h%HomeFolderOverview%</li><!-- START handler -->
	<li><a href="{link}"><img src="{theme_path}/icons/{type}.png" alt="{name}" class="ui-li-icon">{name}</a></li><!-- END handler -->
    <li data-role="list-divider">%h%MainSystem%</li>
	<li><a href="{link_new}"><img src="{theme_path}/icons/new_item.png" alt="%h%MainNew%" class="ui-li-icon">%h%MainNew%</a></li>
	<li><a href="{link_setup}"><img src="{theme_path}/icons/setup_men.png" alt="%h%alt_setup%" class="ui-li-icon">%h%alt_setup%</a></li>
	<li><a href="{link_logout}" rel="external"><img src="{theme_path}/icons/logout_men.png" alt="%h%alt_logout%" class="ui-li-icon">%h%alt_logout%</a></li>
</ul>