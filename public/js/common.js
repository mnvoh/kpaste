/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    common.js
 * @createdat   Jul 22, 2013 4:41:57 PM
 */

/**
 * Stops event propagation(bubbling) on an event
 * 
 * @param {Event} e         the event to act on
 * @returns {void}          none
 */
function stopPropagationOnEvent(e)
{
    var event = e || window.event;
    
    if(event.stopPropagation) {
        event.stopPropagation();
    } else {
        event.cancelBubble = true;
    }
}

/**
 * function to slide down a row in a table, when a button in the previous row was clicked
 * 
 * @param {HtmlElement} button      the button we click on to slide down the row
 * @returns {undefined}             none
 */
function SlideDownNextRowToButtonInTable(button)
{
    var nextRow = $(button).parent().parent().next();
    if( 'none' === nextRow.css('display') )
    {
        nextRow.find('td')
        .wrapInner('<div style="display: none;" />')
        .parent()
        .css('display', 'table-row')
        .find('td > div')
        .slideDown(700, function(){
            var $set = $(this);
            $set.replaceWith($set.contents());
        });
    }
    else
    {
        nextRow.slideUp(300);
    }
}

/**
 * When there's a checkbox in a table row, clicking the row will toggle the checkbox
 * 
 * @param {event} e             event
 * @param {TableRow} tablerow   the table row
 * @returns {undefined}         None
 */
function ToggleCheckBoxInCurrentTableRow(e, tablerow)
{
    stopPropagationOnEvent(e);

    var checkbox = $( tablerow ).find( ':checkbox' );
    var current = checkbox[0].checked;
    //alert(current);
    checkbox.prop( 'checked', !current );
}

/**
 * inverts the selection of all checkboxes in a form
 * 
 * @param {Event e          The click event
 * @param {String} formid   The id of the form containing the checkboxes
 * @returns {void}          None
 */
function ToggleAllCheckboxesInForm( e, formid )
{
    $checkboxes = $( 'form#' + formid ).find(':checkbox');
    var target = e.target;
    $checkboxes.each( function(i, v) {
        if(v !== target)
            v.checked = !v.checked;
    });
}

/**
 * Retrieves a cryptographic-safe random sequence
 * 
 * @param {String} showin       After retrieval, the sequence will be put in a password element with this id
 * @param {String} putin        After retrieval, the sequence will be shown in this element  
 * @param {Int} length          The length of the random sequence    
 * @param {String} charset      The characters to be used in generation
 * @returns {getRandomSequence} Void
 */
function getRandomSequence( showin, putin, length, charset )
{
    if( charset )
        data = { length: length, charset: charset };
    else
        data = { length: length };
    $.ajax({
        type    : 'POST',
        url     : '/en/Ajax/GetRandomSequence',
        data    : data
    }).done( function( msg ) {
        $( '#' + showin ).val( msg );
        $( '#' + putin ).val( msg );
        assessPasswordEntropy(msg);
    });
}

String.prototype.strReverse = function() {
	var newstring = "";
	for (var s=0; s < this.length; s++) {
		newstring = this.charAt(s) + newstring;
	}
	return newstring;
	//strOrig = ' texttotrim ';
	//strReversed = strOrig.revstring();
};


/**
 * Assesses the entropy of a provided password
 * 
 * @param {String} pwd the password
 * 
 * @return {void}
 */
function assessPasswordEntropy( pwd )
{
    // Simultaneous variable declaration and value assignment aren't supported in IE apparently
    // so I'm forced to assign the same value individually per var to support a crappy browser *sigh* 
    var nScore=0, nLength=0, nAlphaUC=0, nAlphaLC=0, nNumber=0, nSymbol=0, nMidChar=0, nRequirements=0, nAlphasOnly=0, nNumbersOnly=0, nUnqChar=0, nRepChar=0, nRepInc=0, nConsecAlphaUC=0, nConsecAlphaLC=0, nConsecNumber=0, nConsecSymbol=0, nConsecCharType=0, nSeqAlpha=0, nSeqNumber=0, nSeqSymbol=0, nSeqChar=0, nReqChar=0, nMultConsecCharType=0;
    var nMultRepChar=1, nMultConsecSymbol=1;
    var nMultMidChar=2, nMultRequirements=2, nMultConsecAlphaUC=2, nMultConsecAlphaLC=2, nMultConsecNumber=2;
    var nReqCharType=3, nMultAlphaUC=3, nMultAlphaLC=3, nMultSeqAlpha=3, nMultSeqNumber=3, nMultSeqSymbol=3;
    var nMultLength=4, nMultNumber=4;
    var nMultSymbol=6;
    var nTmpAlphaUC="", nTmpAlphaLC="", nTmpNumber="", nTmpSymbol="";
    var sAlphaUC="0", sAlphaLC="0", sNumber="0", sSymbol="0", sMidChar="0", sRequirements="0", sAlphasOnly="0", sNumbersOnly="0", sRepChar="0", sConsecAlphaUC="0", sConsecAlphaLC="0", sConsecNumber="0", sSeqAlpha="0", sSeqNumber="0", sSeqSymbol="0";
    var sAlphas = "abcdefghijklmnopqrstuvwxyz";
    var sNumerics = "01234567890";
    var sSymbols = ")!@#$%^&*()";
    var nMinPwdLen = 8;
    if (document.all) { var nd = 0; } else { var nd = 1; }
    if (pwd) {
            nScore = parseInt(pwd.length * nMultLength);
            nLength = pwd.length;
            var arrPwd = pwd.replace(/\s+/g,"").split(/\s*/);
            var arrPwdLen = arrPwd.length;

            /* Loop through password to check for Symbol, Numeric, Lowercase and Uppercase pattern matches */
            for (var a=0; a < arrPwdLen; a++) {
                    if (arrPwd[a].match(/[A-Z]/g)) {
                            if (nTmpAlphaUC !== "") { if ((nTmpAlphaUC + 1) == a) { nConsecAlphaUC++; nConsecCharType++; } }
                            nTmpAlphaUC = a;
                            nAlphaUC++;
                    }
                    else if (arrPwd[a].match(/[a-z]/g)) { 
                            if (nTmpAlphaLC !== "") { if ((nTmpAlphaLC + 1) == a) { nConsecAlphaLC++; nConsecCharType++; } }
                            nTmpAlphaLC = a;
                            nAlphaLC++;
                    }
                    else if (arrPwd[a].match(/[0-9]/g)) { 
                            if (a > 0 && a < (arrPwdLen - 1)) { nMidChar++; }
                            if (nTmpNumber !== "") { if ((nTmpNumber + 1) == a) { nConsecNumber++; nConsecCharType++; } }
                            nTmpNumber = a;
                            nNumber++;
                    }
                    else if (arrPwd[a].match(/[^a-zA-Z0-9_]/g)) { 
                            if (a > 0 && a < (arrPwdLen - 1)) { nMidChar++; }
                            if (nTmpSymbol !== "") { if ((nTmpSymbol + 1) == a) { nConsecSymbol++; nConsecCharType++; } }
                            nTmpSymbol = a;
                            nSymbol++;
                    }
                    /* Internal loop through password to check for repeat characters */
                    var bCharExists = false;
                    for (var b=0; b < arrPwdLen; b++) {
                            if (arrPwd[a] == arrPwd[b] && a != b) { /* repeat character exists */
                                    bCharExists = true;
                                    /* 
                                    Calculate icrement deduction based on proximity to identical characters
                                    Deduction is incremented each time a new match is discovered
                                    Deduction amount is based on total password length divided by the
                                    difference of distance between currently selected match
                                    */
                                    nRepInc += Math.abs(arrPwdLen/(b-a));
                            }
                    }
                    if (bCharExists) { 
                            nRepChar++; 
                            nUnqChar = arrPwdLen-nRepChar;
                            nRepInc = (nUnqChar) ? Math.ceil(nRepInc/nUnqChar) : Math.ceil(nRepInc); 
                    }
            }

            /* Check for sequential alpha string patterns (forward and reverse) */
            for (var s=0; s < 23; s++) {
                    var sFwd = sAlphas.substring(s,parseInt(s+3));
                    var sRev = sFwd.strReverse();
                    if (pwd.toLowerCase().indexOf(sFwd) != -1 || pwd.toLowerCase().indexOf(sRev) != -1) { nSeqAlpha++; nSeqChar++;}
            }

            /* Check for sequential numeric string patterns (forward and reverse) */
            for (var s=0; s < 8; s++) {
                    var sFwd = sNumerics.substring(s,parseInt(s+3));
                    var sRev = sFwd.strReverse();
                    if (pwd.toLowerCase().indexOf(sFwd) != -1 || pwd.toLowerCase().indexOf(sRev) != -1) { nSeqNumber++; nSeqChar++;}
            }

            /* Check for sequential symbol string patterns (forward and reverse) */
            for (var s=0; s < 8; s++) {
                    var sFwd = sSymbols.substring(s,parseInt(s+3));
                    var sRev = sFwd.strReverse();
                    if (pwd.toLowerCase().indexOf(sFwd) != -1 || pwd.toLowerCase().indexOf(sRev) != -1) { nSeqSymbol++; nSeqChar++;}
            }

    /* Modify overall score value based on usage vs requirements */

            /* General point assignment */
            $("nLengthBonus").innerHTML = "+ " + nScore; 
            if (nAlphaUC > 0 && nAlphaUC < nLength) {	
                    nScore = parseInt(nScore + ((nLength - nAlphaUC) * 2));
                    sAlphaUC = "+ " + parseInt((nLength - nAlphaUC) * 2); 
            }
            if (nAlphaLC > 0 && nAlphaLC < nLength) {	
                    nScore = parseInt(nScore + ((nLength - nAlphaLC) * 2)); 
                    sAlphaLC = "+ " + parseInt((nLength - nAlphaLC) * 2);
            }
            if (nNumber > 0 && nNumber < nLength) {	
                    nScore = parseInt(nScore + (nNumber * nMultNumber));
                    sNumber = "+ " + parseInt(nNumber * nMultNumber);
            }
            if (nSymbol > 0) {	
                    nScore = parseInt(nScore + (nSymbol * nMultSymbol));
                    sSymbol = "+ " + parseInt(nSymbol * nMultSymbol);
            }
            if (nMidChar > 0) {	
                    nScore = parseInt(nScore + (nMidChar * nMultMidChar));
                    sMidChar = "+ " + parseInt(nMidChar * nMultMidChar);
            }

            /* Point deductions for poor practices */
            if ((nAlphaLC > 0 || nAlphaUC > 0) && nSymbol === 0 && nNumber === 0) {  // Only Letters
                    nScore = parseInt(nScore - nLength);
                    nAlphasOnly = nLength;
                    sAlphasOnly = "- " + nLength;
            }
            if (nAlphaLC === 0 && nAlphaUC === 0 && nSymbol === 0 && nNumber > 0) {  // Only Numbers
                    nScore = parseInt(nScore - nLength); 
                    nNumbersOnly = nLength;
                    sNumbersOnly = "- " + nLength;
            }
            if (nRepChar > 0) {  // Same character exists more than once
                    nScore = parseInt(nScore - nRepInc);
                    sRepChar = "- " + nRepInc;
            }
            if (nConsecAlphaUC > 0) {  // Consecutive Uppercase Letters exist
                    nScore = parseInt(nScore - (nConsecAlphaUC * nMultConsecAlphaUC)); 
                    sConsecAlphaUC = "- " + parseInt(nConsecAlphaUC * nMultConsecAlphaUC);
            }
            if (nConsecAlphaLC > 0) {  // Consecutive Lowercase Letters exist
                    nScore = parseInt(nScore - (nConsecAlphaLC * nMultConsecAlphaLC)); 
                    sConsecAlphaLC = "- " + parseInt(nConsecAlphaLC * nMultConsecAlphaLC);
            }
            if (nConsecNumber > 0) {  // Consecutive Numbers exist
                    nScore = parseInt(nScore - (nConsecNumber * nMultConsecNumber));  
                    sConsecNumber = "- " + parseInt(nConsecNumber * nMultConsecNumber);
            }
            if (nSeqAlpha > 0) {  // Sequential alpha strings exist (3 characters or more)
                    nScore = parseInt(nScore - (nSeqAlpha * nMultSeqAlpha)); 
                    sSeqAlpha = "- " + parseInt(nSeqAlpha * nMultSeqAlpha);
            }
            if (nSeqNumber > 0) {  // Sequential numeric strings exist (3 characters or more)
                    nScore = parseInt(nScore - (nSeqNumber * nMultSeqNumber)); 
                    sSeqNumber = "- " + parseInt(nSeqNumber * nMultSeqNumber);
            }
            if (nSeqSymbol > 0) {  // Sequential symbol strings exist (3 characters or more)
                    nScore = parseInt(nScore - (nSeqSymbol * nMultSeqSymbol)); 
                    sSeqSymbol = "- " + parseInt(nSeqSymbol * nMultSeqSymbol);
            }

            nScore = (nScore > 100) ? 100 : nScore;
            nScore = (nScore < 0) ? 0 : nScore;
            /* Display updated score criteria to client */
            $('div.passwordStrengthInner').css( 'width', nScore + "%");
            if(nScore < 40)
            {
                $('div.passwordStrengthInner').removeClass('yellow-gradient');
                $('div.passwordStrengthInner').removeClass('green-gradient');
                $('div.passwordStrengthInner').addClass('red-gradient');
            }
            else if(nScore < 70)
            {
                $('div.passwordStrengthInner').removeClass('red-gradient');
                $('div.passwordStrengthInner').removeClass('green-gradient');
                $('div.passwordStrengthInner').addClass('yellow-gradient');
            }
            else
            {
                $('div.passwordStrengthInner').removeClass('yellow-gradient');
                $('div.passwordStrengthInner').removeClass('red-gradient');
                $('div.passwordStrengthInner').addClass('green-gradient');
            }
    }
    else {
        $('div.passwordStrengthInner').css( 'width', "0");   
    }
}

/**
 * Hide any opened popup menu or dialog
 * 
 * @returns {void}
 */
function hideAllPopups() 
{
    $('.popup').fadeOut(100, function(){ $('.popup').remove(); });
    $('.popup-noremove').fadeOut(300);
    if($('#popupLogin').css('display') !== 'none')
        toggleLoginPopup();
}

function showPopup(e, id)
{
    stopPropagationOnEvent(e);
    $('#' + id).fadeIn(300);
}

function showMessageBox(message, type)
{
    message = message.replace(/<iframe/g, '&lt;iframe');
    message = message.replace(/<\/iframe/g, '&lt;/iframe');
    var msgbox = '<div class="msgbox-bg">';
    msgbox += '<div class="' + type + '">'; 
    msgbox += '<div class="closebutton" onclick="$(this).parent().parent().remove();"></div>';
    msgbox += '<p>' + message + '</p><br />';
    msgbox += '</div>';
    
    $('body').append(msgbox);
}

function upVote(pasteid, lang)
{
    $('#upVoteButtonAndText').hide();
    $('#upVoteAjaxLoader').show();
    
    $.getJSON('/' + lang + '/Ajax/ThumbsUpPaste/' + pasteid, function(data) {
        if(data['error'] == true)
        {
            showMessageBox(data['result'], 'error');
        }
        else
        {
            showMessageBox(data['result'], 'notification');
            $('span#thumbsUpCount').html(data['ups']);
            $('div.thumbsUpBar').width(data['upsp']);
        }
        $('#upVoteButtonAndText').show();
        $('#upVoteAjaxLoader').hide();
    });
}

function downVote(pasteid, lang)
{
    $('#downVoteButtonAndText').hide();
    $('#downVoteAjaxLoader').show();
    
    $.getJSON('/' + lang + '/Ajax/ThumbsDownPaste/' + pasteid, function(data) {
        if(data['error'] == true)
        {
            showMessageBox(data['result'], 'error');
        }
        else
        {
            showMessageBox(data['result'], 'notification');
            $('span#thumbsDownCount').html(data['downs']);
            $('div.thumbsUpBar').width(data['upsp']);
        }
        
        $('#downVoteButtonAndText').show();
        $('#downVoteAjaxLoader').hide();
    });
}

function reportPaste(pasteid, lang)
{
    $('#reportbutton').children('span').hide();
    $('#reportbutton').children('img').show();
    
    $.getJSON('/' + lang + '/Ajax/ReportPaste/' + pasteid, function(data) {
        if(data['error'] == true)
        {
            showMessageBox(data['result'], 'error');
        }
        else
        {
            showMessageBox(data['result'], 'notification');
        }
        
        $('#reportbutton').children('img').fadeOut(1000, function() { $('#reportbutton').children('span').show(); } );
    });
}

function checkUsernameExists(errorMessage)
{
    var username = $('#username').val();
    $.post('/en/user/CheckUserExistance', { fieldname: 'username' ,fieldvalue: username } )
    .done(function(data) {
        $('#username').parent().children('ul').remove();
        if(data == 'true')
        {
            $('#username').parent().append('<ul><li>'+errorMessage+'</li></ul>');
        }
    });
}

function checkEmailExists(errorMessage)
{
    var email = $('#email').val();
    $.post('/en/user/CheckUserExistance', { fieldname: 'email' ,fieldvalue: email } )
    .done(function(data) {
        $('#email').parent().children('ul').remove();
        if(data == 'true')
        {
            $('#email').parent().append('<ul><li>'+errorMessage+'</li></ul>');
        }
    });
}

function getUnreadMessagesCount(userid)
{
    $.getJSON('/en/Ajax/GetUnreadMessagesCount/' + userid, function(data) {
        if(!data['error'])
        {
            if(data['result'] > 0)
                $('span.unreadMessagesCount').html('<span class="new">'+data['result']+'</span>');
            else
                $('span.unreadMessagesCount').hide();
            setTimeout(function() { getUnreadMessagesCount(userid); }, 10000);
        }
    });
}

function createFlipBoard(string, size)
{
    var flipBoard = '<span class="digits-holder '+size+'-digits">';
    for(var i = 0; i < string.length; i++)
    {
        flipBoard += '<span class="digit">' + string.charAt(i) + '</span>';
    }
    flipBoard += '</span>';
    document.write(flipBoard);
}

function updateActiveCampaignGraph(campaignid, destinationDiv)
{
    var width = parseInt($('div#'+destinationDiv).width());
    width = (width > 500) ? width : 500;
    var url = '/en/Ajax/getCampaignChart/' + campaignid + '?width=' + width;
    $.getJSON( url, function(data) {
        if(data['chart'])
        {
            $('div#'+destinationDiv).html('<img src="'+data['chart']+'" />');
        }
    });
}

function fetchAds(adTypes, pasteid, lang)
{
    $.getJSON( '/' + lang + '/Ajax/fetchAds/?adtypes=' + adTypes + '&pasteid=' + pasteid, function(data) {
        var adTypesSplit = adTypes.split(',');
        for(var i = 0; i < adTypesSplit.length; i++)
        {
            var adType = adTypesSplit[i].split(':');
            $('.' + adType[0]).each(function( index ) {
                $(this).html(data[adType[0]][index]);
            });
        }

        if(data['iframe'] !== 'NO')
        {
            $('div#iframead').show();
            var width = $('div#iframead').width();
            var height = $('div#iframead').height();
            $('div#iframeWrapper').html("<iframe src='" + data['iframe'] + "' width='" + 
                width + "' height='" + height + "'></iframe>");
            $('div#iframeOpenLink a').attr('href', data['iframe']);
            setTimeout(iframeCountDown, 1000);
        }
        else
        {
            $('div#iframead').hide();
        }
        
        setTimeout(function() { $('div.pasteLoadingDiv').fadeOut(1000); }, 1000);
    });
}

function iframeCountDown()
{
    var time = parseInt($('div#iframeCloseButton span').html());
    if(time <= 0)
    {
        $('div#iframeCloseButton span').html('-');
        $('div#iframeCloseButton span').css('line-height', '1em');
    }
    else
    {
        time--;
        $('div#iframeCloseButton span').html(time);
        setTimeout(iframeCountDown, 1000);
    }
}

function closeIframe()
{
    var time = $('div#iframeCloseButton span').html();
    if(time === '-')
    {
        var bottom = parseInt($('div#iframead').css('bottom'));
        if(bottom >= 0)
        {
            var height = $('div#iframead').height();
            $('div#iframead').animate({bottom: -height}, 'fast');
        }
        else 
        {
            $('div#iframead').animate({bottom: 0}, 'fast');
        }
    }
}

function addAd(addto)
{
    var adClass;
    var adtype = $('input:radio[name=adType]:checked').val();

    switch(adtype)
    {
        case 'squarebutton':
            adClass = (addto === 'bottom') ? 'square_button_b' : 'square_button_ltr';
            break;
        case 'verticalbanner':
            adClass = (addto === 'left' || addto === 'right') ? 'vertical_banner' : null;
            break;
        case 'leaderboard':
            if(addto === 'top')
                adClass = 'leaderboard_t';
            else if(addto === 'bottom')
                adClass = 'leaderboard_b';
            else
                adClass = null;
            break;
        default:
            adClass = null;
    }
    
    if(adClass !== null)
    {
        var element = '<div class="ad ' + adClass + '"><span class="sprite icn-trash cursor-pointer"></span></div>';
        $('div#adsLayout>table td#' + addto).append(element);
        $('div.ad span').click(function() {
            $(this).parent().remove();
        });
    }
}

function saveLayout(url, successMsg, errorMsg)
{
    var top = '{';
    $('td#top div').each(function(index){
        var _class = $(this).attr('class').substr(3);
        top += '"' + index + '"' + ':' + '"' + _class + '",';
    });
    if(top.substr(top.length - 1, 1) == ',')
        top = top.substr(0, top.length - 1);
    top += '}';
    
    var right = "{";
    $('td#right div').each(function(index){
        var _class = $(this).attr('class').substr(3);
        right += '"' + index + '"'  + ':' + '"' + _class + '",';
    });
    if(right.substr(right.length - 1, 1) == ',')
        right = right.substr(0, right.length - 1);
    right += '}';
    
    var bottom = "{";
    $('td#bottom div').each(function(index){
        var _class = $(this).attr('class').substr(3);
        bottom += '"' + index + '"'  + ':' + '"' + _class + '",';
    });
    if(bottom.substr(bottom.length - 1, 1) == ',')
        bottom = bottom.substr(0, bottom.length - 1);
    bottom += '}';
    
    var left = "{";
    $('td#left div').each(function(index){
        var _class = $(this).attr('class').substr(3);
        left += '"' + index + '"'  + ':' + '"' + _class + '",';
    });
    if(left.substr(left.length - 1, 1) == ',')
        left = left.substr(0, left.length - 1);
    left += '}';
    
    var iframe;
    iframe = $('#iframe').is(':checked');
    iframe = (iframe) ? 1 : 0;
    
    $.post(url, {top: top, right: right, bottom: bottom, left: left, iframe: iframe}, function(data) {
        if(data === 'succeeded')
            alert(successMsg);
        else
            alert(errorMsg);
    });
}

function updateTermsPreview(string)
{
    string = string.replace(/<script>|<\?php/, '<!--');
    string = string.replace(/<\/script>|\?>/, '-->');
    $('#preview').html(string);
}
function selectPaste() {
    var doc = document, range, selection;    
    var text = document.getElementById('paste').getElementsByClassName('container')[0];
    if (doc.body.createTextRange) { //ms
        range = doc.body.createTextRange();
        range.moveToElementText(text);
        range.select();
    } else if (window.getSelection) { //all others
        selection = window.getSelection();        
        range = doc.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
    }
}
function selectText(element) {
    var doc = document
        , text = doc.getElementById(element)
        , range, selection
    ;    
    if (doc.body.createTextRange) { //ms
        range = doc.body.createTextRange();
        range.moveToElementText(text);
        range.select();
    } else if (window.getSelection) { //all others
        selection = window.getSelection();        
        range = doc.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

$(document).click(function(e) {
    hideAllPopups();
});

$(document).ready(function(e) {
    $('div#langSelector').hover(function() {
        var height1 = $('div#langSelector .selected').height();
        var height2 = $('div#langSelector .items').height();
        var padding = parseInt($('div#langSelector').css('padding'));
        var height = height1 + height2 + padding;
        $(this).animate({height: height}, 50);
    }, function() {
        $(this).animate({height: 16}, 600);
    });
    
    $('ul.tabs li').click(function() {
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
        var ind = $(this).index() + 1;
        $(this).parent().siblings('div.tabs-contents-wrapper').children('.tabbed-panel').removeClass('active');
        $(this).parent().siblings('div.tabs-contents-wrapper').children('div.tabbed-panel.tab' + ind).addClass('active');
    });
});
//$(document).mousemove(function(e) {
//    if($('#tooltip').css('display') !== 'none')
//    {
//        var mouseX = e.pageX;
//        var mouseY = e.pageY;
//        var width = $('#tooltip').width();
//        var height = $('#tooltip').height();
//        var x = mouseX - width / 2 - 5;
//
//        x = (x > 20) ? x : 20;
//        x = (x < $(document).width() - width - 20) ? x : $(document).width() - width - 20;
//
//        var y = mouseY + 30;
//        y = (y < $(window).height() - height - 30) ? y : $(window).height() - height- 30;
//
//        $('#tooltip').css('left', x + "px");
//        $('#tooltip').css('top', y + "px") ;
//    }
//});