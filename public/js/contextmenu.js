/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    contextmenu.js
 * @createdat   Jul 22, 2013 4:41:57 PM
 */


function campaignContextMenu(e, campaignid, campaignUrl, banner, baseURL)
{
    var translations = new Array();
    var editCampaignUrl = baseURL + '/EditCampaign/' + campaignid;
    var bannerUploadUrl = baseURL + '/UploadBanner/' + campaignid;
    var campaignStatsUrl = baseURL + '/CampaignStats/' + campaignid;
    var extendCampaignUrl = baseURL + '/ExtendCampaign/' + campaignid;
    var pauseCampaignUrl = baseURL + '/PauseCampaign/' + campaignid;
    var resumeCampaignUrl = baseURL + '/ResumeCampaign/' + campaignid;
    
    $('span#campaignCMenuTranslations').children().each(function(i, v){
        translations[i] = v.innerHTML;
    })
  
    $('ul.contextmenu').remove();
    e.preventDefault();
    var menu = '<ul class="contextmenu popup" style="display: none;" onclick="stopPropagationOnEvent(event);">';
    menu += '<li onclick="window.open(\'' + campaignUrl + '\'); hideAllPopups();">' + translations[0] + '</li>';
    menu += '<li class="menu-seperator" onclick="stopPropagationOnEvent(event);"></li>';
    menu += '<li onclick="window.open(\'' + editCampaignUrl + '\'); hideAllPopups();">' + translations[1] + '</li>';
    menu += '<li onclick="window.open(\'' + bannerUploadUrl + '\'); hideAllPopups();">' + translations[2] + '</li>';
    menu += '<li class="menu-seperator" onclick="stopPropagationOnEvent(event);"></li>';
    menu += '<li onclick="window.open(\'' + campaignStatsUrl + '\'); hideAllPopups();">' + translations[3] + '</li>';
    menu += '<li onclick="window.open(\'' + extendCampaignUrl + '\'); hideAllPopups();">' + translations[4] + '</li>';
    menu += '<li onclick="window.open(\'' + pauseCampaignUrl + '\'); hideAllPopups();">' + translations[5] + '</li>';
    menu += '<li onclick="window.open(\'' + resumeCampaignUrl + '\'); hideAllPopups();">' + translations[6] + '</li>';
    menu += '</ul>';
    $('body').append(menu);
    $('ul.contextmenu').css({left: e.pageX + 'px', top: e.pageY + 'px'});
    $('ul.contextmenu').fadeIn(200);
}

function adminContextMenu( e, adminid, baseURL )
{
    var translations = new Array();
    var editPermissionsUrl  = baseURL + '/MasterAdmin/EditPermissions/' + adminid;
    var suspendAdminUrl     = baseURL + '/MasterAdmin/SuspendAdmin/' + adminid;
    var removeSuspensionUrl = baseURL + '/MasterAdmin/RemoveSuspension/' + adminid;
    var deleteAdminUrl      = baseURL + '/MasterAdmin/DeleteAdmin/' + adminid;
    
    $('span#adminCMenuTranslations').children().each(function(i, v){
        translations[i] = v.innerHTML;
    })
  
    $('ul.contextmenu').remove();
    
    e.preventDefault();
    
    var menu = '<ul class="contextmenu popup" style="display: none;" onclick="stopPropagationOnEvent(event);">';
    menu += '<li onclick="document.location.href=\'' + editPermissionsUrl + 
            '\'; hideAllPopups();">' + translations[0] + '</li>';
    menu += '<li onclick="document.location.href=\'' + suspendAdminUrl + 
            '\'; hideAllPopups();">' + translations[1] + '</li>';
    menu += '<li onclick="document.location.href=\'' + removeSuspensionUrl + 
            '\'; hideAllPopups();">' + translations[2] + '</li>';
    menu += '<li onclick="document.location.href=\'' + deleteAdminUrl + 
            '\'; hideAllPopups();">' + translations[3] + '</li>';
    menu += '</ul>';
    $('body').append(menu);
    $('ul.contextmenu').css({left: e.pageX + 'px', top: e.pageY + 'px'});
    $('ul.contextmenu').fadeIn(200);
}

