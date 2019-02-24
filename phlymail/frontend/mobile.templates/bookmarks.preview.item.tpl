<div data-role="page" data-add-back-btn="true" data-back-btn-iconpos="notext" data-back-btn-text="%h%CoreBack%">    
    <div data-role="header" data-position="fixed">
        <h1>{pageTitle}</h1>
        <a href="{PHP_SELF}?{passthru}" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
    </div>
    <div data-role="content">
        <h3><!-- START is_favourite -->
            <img class="h3-icon" src="{theme_path}/icons/favourite_sml.png" alt="" title=""><!-- END is_favourite -->
            {name}
        </h3>        
        <p>
           &rarr; {url}
        </p>
        <p>
           {desc}
        </p>
    </div>
    <div data-role="footer" class="ui-bar" data-position="fixed">    
        <a data-role="button" data-icon="newtab" data-inline="true" href="{url}" data-ajax="false" target="_blank">%h%Open%</a>
        <a data-role="button" data-ajax="false" data-inline="true" href="{edit_url_h}">%h%Edit%</a>
    </div>
</div>