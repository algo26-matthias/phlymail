/**
 * Functionality regarding folder select window in the main site
 * @copyright 2005-2008 phlyLabs Berlin, http://phlymail.com
 * @version 3.9.56 2008-09-09
 */

function fbrowse_create(handler, folder, ops)
{
    if (fbrowse_open) float_close_script('fbrowser');
    var fbc = document.getElementById('flist_browser_container');
    var fb = document.getElementById('flist_browser');
    var PHMc = document.getElementById('PHM_tr_container');
    var mainb = document.getElementById('mainbar');
    fbc.innerHTML = '';
    fb.style.display = 'block';
    fb.style.top = mainb.offsetTop + mainb.offsetHeight + 'px';
    fb.style.left = (PHMc.offsetLeft+4) + 'px';
    fbrowse_draw(fbc, handler, 0);
    fbrowse_open = true;
    fbrowse_folder = folder;
    fbrowse_ops = ops;
    fbrowse_handler = handler;
    fbrowse_target = false;
}

function fbrowse_draw(fbc, handler, childof)
{
    for (var i in flist_srcdat[handler]['childof'][childof]) {
        var CFid = flist_srcdat[handler]['childof'][childof][i];
        if (typeof flist_srcdat[handler]['folders'][CFid] != 'object') continue;
        var cFld = flist_srcdat[handler]['folders'][CFid];
        var div = document.createElement('div');
        div.className = 'foldername';
        div.id = 'fbrowse_fld_' + handler + '_' + CFid;
        var div2 = document.createElement('div');
        div2.className = 'folderlevel';
        if (typeof flist_srcdat[handler]['childof'][CFid] == 'object') div2.className += ' folder_opn_open';
        div2.style.marginLeft = (parseInt(cFld['level'])*16) + 'px';
        div.appendChild(div2);
        var img = document.createElement('img');
        img.src = cFld['icon'];
        img.className = 'foldericon';
        div.appendChild(img);
        var fnam = document.createTextNode(cFld['foldername']);
        fnam.title = cFld['foldername'];
        if (cFld['has_items']) {
            div.className += ' clickable';
        } else {
            div.style.cursor = 'default';
        }
        div.appendChild(fnam);
        fbc.appendChild(div);
        if (typeof flist_srcdat[handler]['childof'][CFid] == 'object') fbrowse_draw(fbc, handler, CFid);
    }
    $('#flist_browser_container .foldername.clickable')
            .click(function () { fbrowse_selectfolder(this.id); })
            .dblclick(function () { fbrowse_selectfolder(this.id, true); });
}

function fbrowse_selectfolder(ID, is_double)
{
    if (fbrowse_target) try { $('#' + fbrowse_target).removeClass('marked'); } catch (e) {}
    $('#' + ID).addClass('marked');
    fbrowse_target = ID;
    if (is_double) { fbrowse_submit(); } else { $('#flist_browser_submit').removeAttr('disabled'); }
}

function fbrowse_submit()
{
    var fTrgt = fbrowse_target.replace(/^fbrowse_fld_/, '').split('_');
    fbrowse_targetHandler = fTrgt[0];
    fbrowse_target = fTrgt[1];
    $('#flist_browser_submit').attr('disabled', true);
    $('#flist_browser').hide();
    eval('collect_and_react_' + fbrowse_handler + '("' + fbrowse_ops + '")');
}