/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    paste.js
 * @createdat   Jul 15, 2013 4:41:57 PM
 */
function updatePastePreview() 
{
    var paste = $('#paste').val();
    paste = paste.replace(/</g, '&lt;');
    var lang = $('#syntax').val();
    if(lang == 'plain')
    {
        paste = paste.replace(/\n/g, '<br />');
        $('#pastePreview').html(paste);
    }
    else
    {
        var code = '<pre id="prePreview" class="brush: ' + lang + '; toolbar: false;">' + paste + '</pre>';
        $('#pastePreview').html(code);
        SyntaxHighlighter.highlight();
    }
}

$('#paste').change(function(){
    updatePastePreview();
});

$('#syntax').change(function() {
    if(!this.value)
        this.selectedIndex = 0;
    updatePastePreview();
});

function path()
{
  var args = arguments,
      result = []
      ;
       
  for(var i = 0; i < args.length; i++)
      result.push(args[i].replace('@', '/pub/sh/current/scripts/'));
       
  return result
};
/* 
SyntaxHighlighter.autoloader.apply(null, path(
  'applescript            @shBrushAppleScript.js',
  'actionscript3 as3      @shBrushAS3.js',
  'bash shell             @shBrushBash.js',
  'coldfusion cf          @shBrushColdFusion.js',
  'cpp c                  @shBrushCpp.js',
  'c# c-sharp csharp      @shBrushCSharp.js',
  'css                    @shBrushCss.js',
  'delphi pascal          @shBrushDelphi.js',
  'diff patch pas         @shBrushDiff.js',
  'erl erlang             @shBrushErlang.js',
  'groovy                 @shBrushGroovy.js',
  'java                   @shBrushJava.js',
  'jfx javafx             @shBrushJavaFX.js',
  'js jscript javascript  @shBrushJScript.js',
  'perl pl                @shBrushPerl.js',
  'php                    @shBrushPhp.js',
  'text plain             @shBrushPlain.js',
  'py python              @shBrushPython.js',
  'ruby rails ror rb      @shBrushRuby.js',
  'sass scss              @shBrushSass.js',
  'scala                  @shBrushScala.js',
  'sql                    @shBrushSql.js',
  'vb vbnet               @shBrushVb.js',
  'xml xhtml xslt html    @shBrushXml.js'
));*/