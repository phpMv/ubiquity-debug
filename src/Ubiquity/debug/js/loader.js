(function () {
    if(typeof jQuery == 'undefined'){
        insertJS("https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js");
    }

    window.deferQ=function (method) {
        if (window.jQuery) method();
        else
            setTimeout(function() { deferQ(method) }, 50);};

    window.deferQ(function(){
        start();
    });


    function insertJS(js,load){
        let oScriptElem = document.createElement("script");
        oScriptElem.type = "text/javascript";
        oScriptElem.src = js;
        oScriptElem.setAttribute('data-manual',true);
        if(load) {
            oScriptElem.addEventListener('load', load);
        }
        document.head.insertBefore(oScriptElem, document.head.getElementsByTagName("script")[0])
    }

    function insertCSS(css,load){
        let exists=document.querySelector("link[href='"+css+"']");
        if (!exists) {
            var head  = document.getElementsByTagName('head')[0];
            var link  = document.createElement('link');
            link.rel  = 'stylesheet';
            link.type = 'text/css';
            link.href = css;
            if(load){
                link.addEventListener('load', load);
            }
            head.appendChild(link);
        }
    }

    function loadUI(){
        if (!window.jQuery || $('.ui.accordion')==undefined || !$('.ui.accordion').accordion) return setTimeout(loadUI, 50);
        $('.ui.accordion').accordion({exclusive:false});
        $('body').on('click','.ui.stack.toggle',function (){
            let accordion=$(this).siblings('.accordion');
            accordion.find('.title').each(function(){
                $(this).click();
            });
        });
        $('body').on('click','.ui.parameters.toggle',function (){
            $(this).siblings('table').toggle();
        });
        $('.ui.button.toggle').state();
        $('.menu .item').tab();
        $('i.var_error').popup();

        $('.display_var').click(function(){
            let id=$(this).attr('data-id');
            $(this).closest('.menu').children('.item').removeClass('active');
            $(this).toggleClass('active');
            let elm=$('#ve-'+id).clone();
            elm.id='';
            $(this).closest('.variable_container').find('.variable_content').html('').append(elm);
            $('i.var_error').popup();
        });
    }

    function loadPopups(){
        if (!window.jQuery || !$('.ui.popup').popup ) return setTimeout(loadPopups, 50,popups);
        for (const [key, value] of Object.entries(popups)) {
            for (const [argName, argValue] of Object.entries(value)) {
                loadPopup(key,argName,argValue.name,argValue.value);
            }
        }
    }

    function loadPopup(key,name,effectiveName,value){
        if (!$("#"+key +" .token").length) return setTimeout(loadPopup, 50,key,name,effectiveName,value);
        let contains=effectiveName || name;
        $("#"+key+" mark").each(function(){
            let elm=$(this);
            if(elm.text()===contains){
               elm.popup({title:name,content: value});
           }
        });
    }

    function loadPrism(){
        if(typeof Prism === 'undefined' || !Prism.languages.php || !window.prismCssLoaded) return setTimeout(loadPrism, 50);
            Prism.highlightAll();
    }

    function start(){
        $(document).ready(function(){
            if(!$(document).accordion) {
                insertCSS("https://cdn.jsdelivr.net/npm/fomantic-ui@2.8.7/dist/semantic.min.css");
                insertJS("https://cdn.jsdelivr.net/npm/fomantic-ui@2.8.7/dist/semantic.min.js");
            }
            insertCSS('/vendor/phpmv/ubiquity-debug/src/Ubiquity/debug/assets/styles.css');
            if(typeof Prism === 'undefined') {
                let root='/vendor/prismjs/prism/';
                let cssRoot=root+'themes/';
                insertCSS(cssRoot+'prism-okaidia.css',function(){window.prismCssLoaded=true;});
                insertCSS(root+'plugins/line-numbers/prism-line-numbers.css');
                insertCSS(root+'plugins/line-highlight/prism-line-highlight.css');
                insertJS(root+'prism.js',function(){
                   insertJS(root+'plugins/keep-markup/prism-keep-markup.js');
                    insertJS(root+'components/prism-markup-templating.js');
                    insertJS(root+'components/prism-php.js');
                    insertJS(root+'plugins/line-numbers/prism-line-numbers.js');
                    insertJS(root+'plugins/line-highlight/prism-line-highlight.js');
                });

            }
            loadUI();
            loadPrism();
            if(typeof popups !== 'undefined') {
                loadPopups();
            }
        });
    }
}());
