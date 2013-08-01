var SVGNS='http://www.w3.org/2000/svg',XLINKNS='http://www.w3.org/1999/xlink';

function textrotate_make_svg(el, last)
{
  var string=el.firstChild.nodeValue;

  // Add absolute-positioned string (to measure length)
  var abs=document.createElement('div');
  abs.appendChild(document.createTextNode(string));
  abs.style.position='absolute';
  el.parentNode.insertBefore(abs,el);
  var textWidth=abs.offsetWidth,textHeight=abs.offsetHeight;
  if (last) {
    textWidth = textWidth * 1.3;
  }
  el.parentNode.removeChild(abs);

  // Create SVG
  var svg=document.createElementNS(SVGNS,'svg');
  svg.setAttribute('version','1.1');
  var width=(textHeight*9)/8;
  svg.setAttribute('width',width);
  svg.setAttribute('height',textWidth+20);

  // Add text
  var text=document.createElementNS(SVGNS,'text');
  svg.appendChild(text);
  text.setAttribute('x',textWidth);
  text.setAttribute('y',-textHeight/4);
  text.setAttribute('text-anchor','end');
  text.setAttribute('transform','rotate(90)');
  text.appendChild(document.createTextNode(string));

  // Is there an icon near the text?
  var icon=el.parentNode.firstChild;
  if(icon.nodeName.toLowerCase()=='img') {
    el.parentNode.removeChild(icon);
    var image=document.createElementNS(SVGNS,'image');
    var iconx=el.offsetHeight/4;
    if(iconx>width-16) iconx=width-16;
    image.setAttribute('x',iconx);
    image.setAttribute('y',textWidth+4);
    image.setAttribute('width',16);
    image.setAttribute('height',16);
    image.setAttributeNS(XLINKNS,'href',icon.src);
    svg.appendChild(image);
  }

  // Replace original content with this new SVG
  el.parentNode.insertBefore(svg,el);
  el.parentNode.removeChild(el);
}

function block_ilp_textrotate_init() {
    YUI().use('yui2-dom', function(Y) {
      var elements= Y.YUI2.util.Dom.getElementsByClassName('capabilityname', 'span');
      for(var i=0;i<elements.length;i++)
      {
       var last = false;
       if (i == elements.length -1) {
          last = true;
       }
        var el=elements[i];
        el.parentNode.style.verticalAlign='bottom';
        el.parentNode.style.textAlign = 'right';
        textrotate_make_svg(el, last);

      }
    });
}

