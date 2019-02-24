<!-- START main --><!-- START return -->
<div align="left" style="max-height: 250px; overflow:auto;"><strong>{WP_return}</strong><br /><br /></div><!-- END return -->
<script type="text/javascript">
baseurl = '{baseurl}';
myaction = 'checkkey';
v_inst = '{version_installed}';
relstatus = 'stable';
dl_num = 0;
dl_cnt = 0;
function switchaction()
{
    urladd = '';
    switch (myaction) {
    case 'checkkey':
        document.getElementById('m_checkkey').className = 'au_done';
        break;
    case 'getfilelist':
        document.getElementById('m_fetchlist').className = 'au_done';
        break;
    case 'checklist':
        document.getElementById('m_checklist').className = 'au_done';
        break;
    case 'download':
        urladd = '&download=' + dl_cnt;
        dl_cnt++;
        document.getElementById('k_downloading').innerHTML = dl_cnt + ' / ' + dl_num;
        break;
    }
    $.ajax({url: baseurl + myaction + '&relstatus=' + relstatus + urladd, success: AJAX_process});
}

function AJAX_process(next)
{
    if (next['newversion']) {
        document.getElementById('m_checkkey').className = 'au_done';
        if (next['error']) {
            if (400 == next['error']) {
                document.getElementById('checkkey_wrongIP').style.display = 'inline';
            } else if (501 == next['error']) {
                document.getElementById('checkkey_expired').style.display = 'inline';
            } else {
                document.getElementById('checkkey_invalid').style.display = 'inline';
            }
            document.getElementById('m_checkversion').className = 'au_done';
            document.getElementById('checkversion_failed').style.display = 'inline';
        } else {
            document.getElementById('checkkey_ok').style.display = 'inline';
            document.getElementById('checkversion_ok').style.display = 'inline';
            document.getElementById('m_checkversion').className = 'au_done';
            document.getElementById('m_v_installed').className = 'au_done';
            document.getElementById('k_v_installed').className = 'au_done';
            document.getElementById('m_v_server').className = 'au_done';
            document.getElementById('k_v_server').className = 'au_done';
            document.getElementById('stable_info').style.visibility = 'visible';
            document.getElementById('k_v_server').innerHTML = next['newversion'];
            if (next['newbeta']) {
                document.getElementById('k_beta_server').innerHTML = next['newbeta'];
                document.getElementById('k_beta_server').className = 'au_done';
                document.getElementById('beta_info').style.visibility = 'visible';
                if (v_inst.replace(/\./, '') < next['newbeta'].replace(/\./, '')) {
                    document.getElementById('b_betastartupdate').style.display = 'block';
                    myaction = 'getfilelist';
                }

            } else {
                next['newbeta'] = next['newversion'];
            }
            if (v_inst.replace(/\./, '') < next['newversion'].replace(/\./, '')) {
                document.getElementById('b_startupdate').style.display = 'block';
                myaction = 'getfilelist';
            }
            if (v_inst.replace(/\./, '') >= next['newversion'].replace(/\./, '')
                    && v_inst.replace(/\./, '') >= next['newbeta'].replace(/\./, '')) {
                document.getElementById('m_uptodate').style.display = 'block';
            }
        }
    }
    if (next['gotfilelist']) {
        if (next['error']) {
            document.getElementById('fetchlist_failed').style.display = 'inline';
        } else {
            document.getElementById('fetchlist_ok').style.display = 'inline';
            myaction = 'checklist';
            switchaction();
        }
    }
    if (next['checkedlist']) {
        document.getElementById('checklist_ok').style.display = 'inline';
        myaction = 'download_init';
        switchaction();
    }
    if (next['dlinit_ok']) {
        document.getElementById('m_downloading').className = 'au_done';
        dl_num = next['dlinit_ok'] * 1;
        document.getElementById('k_downloading').innerHTML = dl_cnt + ' / ' + dl_num;
        myaction = 'download';
        switchaction();
    }
    if (next['downloaded']) {
        if (next['error'] && next['error'] == '-3') {
            document.getElementById('dl_retry').className = 'au_done';
            document.getElementById('dl_abort').className = 'au_done';
            return;
        }
        if (dl_cnt == dl_num) {
            document.getElementById('m_installing').className = 'au_done';
            myaction = 'install';
        }
        switchaction();
    }
    if (next['installed']) {
        if (next['failed'] != 0) {
            document.getElementById('install_failed').style.display = 'inline';
            // document.getElementById('inst_retry').className = 'au_done';
            document.getElementById('inst_abort').className = 'au_done';
            alert('{msg_install_failed_num}'.replace(/\$1/, next['failed']).replace(/\$2/, (next['failed']*1)+(next['installed']*1)));
        } else {
            document.getElementById('install_ok').style.display = 'inline';
            self.location.href = baseurl + 'AUdone&WP_return=' + encodeURIComponent('{msg_install_okay}');
        }
    }
}

function view_changelog(relstat)
{
    window.open
            (baseurl + 'changelog&relstatus=' + relstat
            ,'cfg_changelog'
            ,'top=200,left=200,width=500,height=600,scrollbars=yes,resizable=yes,locationbar=no,statusbar=no,personalbar=no'
            )
}


function retry_download()
{
    dl_cnt--;
    document.getElementById('dl_retry').className = 'au_hidden';
    document.getElementById('dl_abort').className = 'au_hidden';
    switchaction();
}

function retry_install()
{
    myaction = 'install'
    document.getElementById('inst_retry').className = 'au_hidden';
    document.getElementById('inst_abort').className = 'au_hidden';
    switchaction();
}

function abort_download()
{
    self.location.href = baseurl + 'AUdone&WP_return=' + encodeURIComponent('{msg_dl_aborted}');
}

function abort_install()
{
    self.location.href = baseurl + 'AUdone&WP_return=' + encodeURIComponent('{msg_inst_aborted}');
}

window.onload = switchaction;
</script>
<div align="left">
 <table border="0" cellpadding="2" cellspacing="0" width="100%">
  <tr>
   <td align="left" valign="top" id="m_checkkey" class="au_greyed">{msg_checkingkey}</td>
   <td>
    <span id="checkkey_ok" style="display:none" class="au_ok">{msg_ok}</span>
    <span id="checkkey_expired" style="display:none" class="au_failed">{msg_expired}</span>
    <span id="checkkey_wrongIP" style="display:none" class="au_failed">{msg_wrongIP}</span>
    <span id="checkkey_invalid" style="display:none" class="au_failed">{msg_invalid}</span>
   </td>
  </tr>
  <tr>
   <td align="left" valign="top" id="m_checkversion" class="au_greyed">{msg_checkingversion}</td>
   <td>
    <span id="checkversion_ok" style="display:none" class="au_ok">{msg_ok}</span>
    <span id="checkversion_failed" style="display:none" class="au_failed">{msg_failed}</span>
   </td>
  </tr>
  <tr>
   <td align="left" valign="top" id="m_v_installed" class="au_greyed">- {msg_version_installed}:</td>
   <td id="k_v_installed" class="au_greyed">{version_installed}</td>
  </tr>
  <tr>
   <td align="left" valign="top" id="m_v_server" class="au_greyed" colspan="2">- {msg_version_server}:<br />
    <table border="0" cellpadding="2" cellspacing="0" style="width:100%">
     <tr id="stable_info" style="visibility:hidden;">
      <td id="k_v_server" class="au_greyed"> </td>
      <td style="color:darkgreen;font-weight:bold;"> (stable) &nbsp;</td>
      <td><a href="javascript:view_changelog('stable');">ChangeLog</a></td>
      <td><button type="button" id="b_startupdate" style="display:none;" onclick="relstatus='stable';switchaction();">{msg_runAU}</button></td>
     </tr>
     <tr id="beta_info" style="visibility:hidden;">
      <td id="k_beta_server" class="au_greyed"> </td>
      <td style="color:darkred;font-weight:bold;"> (BETA) &nbsp;</td>
      <td><a href="javascript:view_changelog('beta');">ChangeLog</a></td>
      <td><button type="button" id="b_betastartupdate" style="display:none;" onclick="relstatus='beta';switchaction();">{msg_runAU}</button></td>
     </tr>
    </table>
   </td>
  </tr>
  <tr>
   <td align="left" valign="top" colspan="2">
    <div class="au_done" style="display:none;" id="m_uptodate">{msg_uptodate}</div>
   </td>
  </tr>
  <tr>
   <td align="left" valign="top" id="m_fetchlist" class="au_hidden">{msg_fetchinglist}</td>
   <td id="k_fetchlist">
    <span id="fetchlist_ok" style="display:none" class="au_ok">{msg_ok}</span>
    <span id="fetchlist_failed" style="display:none" class="au_failed">{msg_failed}</span>
   </td>
  </tr>
  <tr>
   <td align="left" valign="top" id="m_checklist" class="au_hidden">{msg_checkinglist}</td>
   <td id="k_checklist">
    <span id="checklist_ok" style="display:none" class="au_ok">{msg_ok}</span>
    <span id="checklist_failed" style="display:none" class="au_failed">{msg_failed}</span>
   </td>
  </tr>
  <tr>
   <td align="left" valign="top" id="m_downloading" class="au_hidden">{msg_downloadingfiles}</td>
   <td id="k_downloading" class="au_done"> </td>
  </tr>
  <tr>
   <td align="left" valign="top" id="dl_retry" class="au_hidden"><button type="button" onlcik="retry_download();">{msg_retry}</button></td>
   <td align="left" valign="top" id="dl_abort" class="au_hidden"><button type="button" onlcik="abort_download();">{msg_abort}</button></td>
  </tr>
  <tr>
   <td align="left" valign="top" id="m_installing" class="au_hidden"> </td>
   <td id="k_installing">
    <span id="install_ok" style="display:none" class="au_ok">{msg_ok}</span>
    <span id="install_failed" style="display:none" class="au_failed">{msg_failed}</span>
   </td>
  </tr>
  <tr>
   <td align="left" valign="top" id="inst_retry" class="au_hidden"><button type="button" onlcik="retry_install();">{msg_retry}</button></td>
   <td align="left" valign="top" id="inst_abort" class="au_hidden"><button type="button" onlcik="abort_install();">{msg_abort}</button></td>
  </tr>
 </table>
</div><!-- END main -->