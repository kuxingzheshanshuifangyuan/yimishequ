 // 代码来自于手机网易，是基于750px的设计稿进行计算的
        var Dpr = 1, uAgent = window.navigator.userAgent;
        var isIOS = uAgent.match(/iphone/i);
        var isYIXIN = uAgent.match(/yixin/i);
        var is2345 = uAgent.match(/Mb2345/i);
        var ishaosou = uAgent.match(/mso_app/i);
        var isSogou = uAgent.match(/sogoumobilebrowser/ig);
        var isLiebao = uAgent.match(/liebaofast/i);
        var isGnbr = uAgent.match(/GNBR/i);
        function resizeRoot(){
            var wWidth = (screen.width > 0) ? (window.innerWidth >= screen.width || window.innerWidth == 0) ? screen.width : window.innerWidth : window.innerWidth, wDpr, wFsize;
            var wHeight = (screen.height > 0) ? (window.innerHeight >= screen.height || window.innerHeight == 0) ? screen.height : window.innerHeight : window.innerHeight;
            if (window.devicePixelRatio) {
                wDpr = window.devicePixelRatio;
            } else {
                wDpr = isIOS ? wWidth > 818 ? 3 : wWidth > 480 ? 2 : 1 : 1;
            }
            if(isIOS) {
                wWidth = screen.width;
                wHeight = screen.height;
            }
            // if(window.orientation==90||window.orientation==-90){
            //     wWidth = wHeight;
            // }else if((window.orientation==180||window.orientation==0)){
            // }
            if(wWidth > wHeight){
                wWidth = wHeight;
            }
            wFsize = wWidth > 1080 ? 144 : wWidth / 7.5;
            wFsize = wFsize > 32 ? wFsize : 32;
            window.screenWidth_ = wWidth;
            if(isYIXIN || is2345 || ishaosou || isSogou || isLiebao || isGnbr){//YIXIN 和 2345 这里有个刚调用系统浏览器时候的bug，需要一点延迟来获取
                setTimeout(function(){
                    wWidth = (screen.width > 0) ? (window.innerWidth >= screen.width || window.innerWidth == 0) ? screen.width : window.innerWidth : window.innerWidth;
                    wHeight = (screen.height > 0) ? (window.innerHeight >= screen.height || window.innerHeight == 0) ? screen.height : window.innerHeight : window.innerHeight;
                    wFsize = wWidth > 1080 ? 144 : wWidth / 7.5;
                    wFsize = wFsize > 32 ? wFsize : 32;
                    // document.getElementsByTagName('html')[0].dataset.dpr = wDpr;
                    document.getElementsByTagName('html')[0].style.fontSize = wFsize + 'px';
                    // document.getElementById("fixed").style.display="none";
                },500);
            }else{
                // document.getElementsByTagName('html')[0].dataset.dpr = wDpr;
                document.getElementsByTagName('html')[0].style.fontSize = wFsize + 'px';
                // document.getElementById("fixed").style.display="none";
            }
            // alert("fz="+wFsize+";dpr="+window.devicePixelRatio+";UA="+uAgent+";width="+wWidth+";sw="+screen.width+";wiw="+window.innerWidth+";wsw="+window.screen.width+window.screen.availWidth);
        }
        resizeRoot();
   //***************************************************注意****************************************
   //   这里还得设置一下body的属性：主要就是防止在平板或者电脑上时可以居中显示
   //   body{
   //       width: 7.5rem;
   //       font-size: .32rem;
   //       max-width: 1080px;
   //       margin: 0 auto;
   //       background: #f6f6f6;
   //       font-family: 'STHeiti','Microsoft YaHei',Helvetica,Arial,sans-serif;
   //       -webkit-tap-highlight-color: rgba(0,0,0,0);
   //   } 
   //********************************************************************************************** 