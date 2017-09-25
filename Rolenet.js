// get URI of this script
var scripts = document.getElementsByTagName("script");
var src = scripts[scripts.length-1].src;

// sigma autoloader FAILED, synchronism problem, script tags in html file is easier than everything
;(function() {
  'use strict';
  /*
  if ("onhashchange" in window) {
    window.addEventListener("hashchange", function() {
      window.scrollBy(0, -60);
    }, false);

  };
  */
   /**
   * Return the coordinates of the two control points for a self loop (i.e.
   * where the start point is also the end point) computed as a cubic bezier
   * curve.
   *
   * @param  {number} x    The X coordinate of the node.
   * @param  {number} y    The Y coordinate of the node.
   * @param  {number} size The node size.
   * @return {x1,y1,x2,y2} The coordinates of the two control points.
   */
  sigma.utils.dramaSelf = function(x , y, size) {
    return {
      x1: x - size * 6,
      y1: y,
      x2: x,
      y2: y + size * 6
    };
  };
 /**
   * Return the control point coordinates for a quadratic bezier curve.
   *
   * @param  {number} x1  The X coordinate of the start point.
   * @param  {number} y1  The Y coordinate of the start point.
   * @param  {number} x2  The X coordinate of the end point.
   * @param  {number} y2  The Y coordinate of the end point.
   * @return {x,y}        The control point coordinates.
   */
  sigma.utils.dramaTarget = function(x1, y1, x2, y2) {
    return {
      x: (x1 + x2) / 2 + (y2 - y1) / 20,
      y: (y1 + y2) / 2 + (x1 - x2) / 20
    };
  };
  /**
   * The default node renderer. It renders the node as a simple disc.
   *
   * @param  {object}                   node     The node object.
   * @param  {CanvasRenderingContext2D} context  The canvas context.
   * @param  {configurable}             settings The settings function.
   */
  sigma.canvas.nodes.drama = function(node, context, settings) {
    context.save();
    // a default ratio % size of canvas
    var scale = settings('scale') || 1;
    var prefix = settings('prefix') || '';

    if ( settings('bw') ) context.fillStyle = settings('defaultNodeColor');
    else context.fillStyle = node.color || settings('defaultNodeColor');
    context.beginPath();
    // calculate a size relative to global canvas
    var size = node[prefix + 'size'] * scale ;
    context.arc(
      node[prefix + 'x'],
      node[prefix + 'y'],
      size,
      0,
      Math.PI * 2,
      true
    );

    context.closePath();
    context.fill();
    context.strokeStyle = "#999";
    context.lineWidth = 1;
    context.stroke();
    context.restore();
  };

  sigma.utils.pkg('sigma.canvas.labels');
  /**
   * This label renderer will just display the label on the right of the node.
   *
   * @param  {object}                   node     The node object.
   * @param  {CanvasRenderingContext2D} context  The canvas context.
   * @param  {configurable}             settings The settings function.
   */
  sigma.canvas.labels.drama = function(node, context, settings) {
    if (!node.label || typeof node.label !== 'string')
      return;
    var prefix = settings('prefix') || '';
    // no labels for little nodes
    if (node[prefix + 'size'] < settings('labelThreshold'))
      return;
    context.save();
    var scale = (settings('scale'))?settings('scale'):1;
    // node size relative to global size
    var nodeSize = node[prefix + 'size'] * scale * 0.7;
    // fontSize relative to nodeSize
    var fontSize = (settings('labelSize') === 'fixed') ?
      settings('defaultLabelSize') :
      settings('defaultLabelSize') + settings('labelSizeRatio') * (nodeSize - settings('minNodeSize'));
    // default font ?

    var height = parseInt(fontSize);
    var y = Math.round(node[prefix + 'y'] + nodeSize * 0.6);

    var small = 25;
    context.lineWidth = 1;
    // bg color
    if ( fontSize <= small) {
      context.font = fontSize+'px '+settings('font');
      var width = Math.round(context.measureText(node.label).width);
      var x = Math.round(node[prefix + 'x'] - (width / 2) );
      context.fillStyle = 'rgba(255, 255, 255, 0.6)';
      context.fillRect(x-fontSize*0.2, y - fontSize + fontSize/10, width+fontSize*0.4, height);
    }
    else {
      context.font = settings('fontStyle')+' '+fontSize+'px '+settings('font');
      var width = Math.round(context.measureText(node.label).width);
      var x = Math.round(node[prefix + 'x'] - (width / 2) );
      context.fillStyle = 'rgba(255, 255, 255, 0.2)';
      context.fillRect(x-fontSize*0.2, y - fontSize + fontSize/10, width+fontSize*0.4, height);
    }
    // text color
    if (settings('labelColor') === 'node')
      context.fillStyle = (node.color || settings('defaultNodeColor'));
    else
      context.fillStyle = settings('defaultLabelColor');

    context.fillText( node.label, x, y);

    /* border text ?
    if (settings('labelStrokeStyle') && fontSize > small) {
      context.strokeStyle = settings('labelStrokeStyle');
      context.strokeText(node.label, x, y);
    }
    */
    context.restore();
  };

  // Initialize packages:
    sigma.utils.pkg('sigma.canvas.hovers');

  /**
   * This hover renderer will basically display the label with a background.
   *
   * @param  {object}                   node     The node object.
   * @param  {CanvasRenderingContext2D} context  The canvas context.
   * @param  {configurable}             settings The settings function.
   */
  sigma.canvas.hovers.def = function(node, context, settings) {
    // nothing to display, go out
    if (!node.label || typeof node.label !== 'string') return;
    var scale = (settings('scale'))?settings('scale'):1;
    context.save();
        var x,
        y,
        w,
        h,
        e,
        fontStyle = settings('hoverFontStyle') || settings('fontStyle'),
        prefix = settings('prefix') || '',
        nodeSize = node[prefix + 'size'] * scale,
        fontSize = settings('defaultLabelSize'); // default font-size


    context.beginPath();
    context.fillStyle = settings('labelHoverBGColor') === 'node' ?
      (node.color || settings('defaultNodeColor')) :
      settings('defaultHoverLabelBGColor');

    if (settings('labelHoverShadow')) {
      context.shadowOffsetX = 0;
      context.shadowOffsetY = 0;
      context.shadowBlur = 8;
      context.shadowColor = settings('labelHoverShadowColor');
    }

    var text = node.label;
    if (node.title)
      text += ', '+node.title;
    context.font = fontSize + 'px sans-serif';
    x = Math.round(node[prefix + 'x'] - fontSize / 2 - 2);
    y = Math.round(node[prefix + 'y'] - fontSize / 2 - 2);
    w = Math.round(
      context.measureText(text).width + fontSize/2 + nodeSize + 7
    );
    h = Math.round(fontSize + 4);
    e = Math.round(fontSize / 2 + 2);


    context.moveTo(x, y + e);
    context.arcTo(x, y, x + e, y, e);
    context.lineTo(x + w, y);
    context.lineTo(x + w, y + h);
    context.lineTo(x + e, y + h);
    context.arcTo(x, y + h, x, y + h - e, e);
    context.lineTo(x, y + e);

    context.closePath();
    context.fill();

    context.shadowOffsetX = 0;
    context.shadowOffsetY = 0;
    context.shadowBlur = 0;


    // Node border:
    if (settings('borderSize') > 0) {
      context.beginPath();
      context.fillStyle = settings('nodeBorderColor') === 'node' ?
        (node.color || settings('defaultNodeColor')) :
        settings('defaultNodeBorderColor');
      context.arc(
        node[prefix + 'x'],
        node[prefix + 'y'],
        nodeSize + settings('borderSize'),
        0,
        Math.PI * 2,
        true
      );
      context.closePath();
      context.fill();
    }

    // Node:
    var nodeRenderer = sigma.canvas.nodes[node.type] || sigma.canvas.nodes.def;
    nodeRenderer(node, context, settings);
    // Label background:

    // Display the text:
    context.fillStyle = (settings('labelHoverColor') === 'node') ?
      (node.color || settings('defaultNodeColor')) :
      settings('defaultLabelHoverColor');

    context.fillText(
      text,
      Math.round(node[prefix + 'x'] + nodeSize + 3),
      Math.round(node[prefix + 'y'] + fontSize / 3)
    );
    context.restore();
  };


  sigma.utils.pkg('sigma.canvas.edges');
  /**
   * This edge renderer will display edges as arrows going from the source node
   *
   * @param  {object}                   edge         The edge object.
   * @param  {object}                   source node  The edge source node.
   * @param  {object}                   target node  The edge target node.
   * @param  {CanvasRenderingContext2D} context      The canvas context.
   * @param  {configurable}             settings     The settings function.
   */
  sigma.canvas.edges.drama = function(edge, source, target, context, settings) {
    var color = edge.color,
        prefix = settings('prefix') || '',
        edgeColor = settings('edgeColor'),
        defaultNodeColor = settings('defaultNodeColor'),
        defaultEdgeColor = settings('defaultEdgeColor'),
        cp = {},
        size = Math.max(edge[prefix + 'size']),
        sX = source[prefix + 'x'],
        sY = source[prefix + 'y'],
        tX = target[prefix + 'x'],
        tY = target[prefix + 'y'],
        aSize = Math.max(size * 2.5, settings('minArrowSize')),
        d,
        oX,
        oY,
        aX,
        aY,
        vX,
        vY
    ;
    size= Math.max(size - 5, 0)  ;
    var scale = (settings('scale'))?settings('scale'):1;
    // calculate a size relative to global canvas
    size = size * scale * 1.5;
    if (!color)
      switch (edgeColor) {
        case 'source':
          color = source.color || defaultNodeColor;
          break;
        case 'target':
          color = target.color || defaultNodeColor;
          break;
        default:
          color = defaultEdgeColor;
          break;
      }

    // self loop, no arrow needed
    if (source.id === target.id) {
      if (settings('bw')) context.strokeStyle = settings('defaultEdgeColor');
      else context.strokeStyle = color;
      context.lineWidth = size;
      context.beginPath();
      context.moveTo(sX, sY);
      cp = sigma.utils.dramaSelf(sX, sY, size);
      context.bezierCurveTo(cp.x1, cp.y1, cp.x2, cp.y2, tX, tY);
      context.stroke();
    }
    // target edge, arrow
    else {

      /*
              1---2
                   \
        source      3 target
                   /
              5---4
      */

      // distance from center of source to target
      var d = Math.sqrt(Math.pow(tX - sX, 2) + Math.pow(tY - sY, 2)) ;
      // source and target size
      var sSize = source[prefix + 'size'] * scale ;
      var tSize = target[prefix + 'size'] * scale ;
      context.beginPath();
      context.globalCompositeOperation='destination-over';
      // start arrow outside the circle
      var dX = (tY - sY) * (size/2) / d;
      var dY = -(tX - sX) * (size/2) / d;
      context.moveTo(sX + dX, sY + dY); // 1
      var bX = sX + (tX - sX) * (d - tSize - d*0.07 - size*0.5) / d;
      var bY = sY + (tY - sY) * (d - tSize - d*0.07 - size*0.5) / d;
      context.lineTo(bX + dX, bY + dY); // 2
      var aX = sX + (tX - sX) * ( d - tSize - d*0.07 ) / d ;
      var aY = sY + (tY - sY) * ( d - tSize - d*0.07 ) / d ;
      context.lineTo(aX , aY ); // 4
      context.lineTo(bX - dX, bY - dY); // 6
      context.lineTo(sX - dX, sY - dY ); // 5
      context.lineWidth = 0.5;
      context.strokeStyle = '#999';
      context.stroke();
      context.closePath();
      if (settings('bw')) context.fillStyle = settings('defaultEdgeColor');
      else context.fillStyle = color;
      context.fill();

      context.globalCompositeOperation='source-over';
      context.beginPath();
      context.lineWidth = 4;
      context.strokeStyle = 'rgba(0, 0, 0, 0.7)';
      context.moveTo(bX + dX, bY + dY); // 2
      context.lineTo(aX , aY ); // 4
      context.lineTo(bX - dX, bY - dY); // 6
      context.stroke();
      context.closePath();

    }
  };



  window.Rolenet = function ( canvas, data, maxNodeSize ) {
    this.src = src; // store the global
    this.workerUrl = this.src.substr( 0, this.src.lastIndexOf("/")+1 )+"sigma/worker.js";
    this.canvas = document.getElementById(canvas);


    this.odata = data;
    //
    var height = this.canvas.offsetHeight;
    // adjust maxnode size to screen height
    var scale = Math.max( height, 150) / 700;
    if ( !maxNodeSize ) maxNodeSize = height/25;
    else maxNodeSize = maxNodeSize * scale;
    var width = this.canvas.offsetWidth;

    this.sigma = new sigma({
      graph: data,
      renderer: {
        container: this.canvas,
        type: 'canvas'
      },
      settings: {
        defaultEdgeColor: 'rgba(128, 128, 128, 0.2)',
        defaultNodeColor: "rgba(230, 230, 230, 0.7)",
        edgeColor: "default",
        drawLabels: true,
        defaultLabelSize: 14,
        defaultLabelColor: "rgba(0, 0, 0, 0.7)",
        labelStrokeStyle: "rgba(255, 255, 255, 0.7)",
        labelThreshold: 0,
        labelSize:"proportional",
        labelSizeRatio: 1.2,
        font: ' Tahoma, Geneva, sans-serif', // after fontSize
        fontStyle: ' bold ', // before fontSize
        /* marche mais trop grand avec les commentaires
        labelSize: "proportional",
        */
        height: height,
        width: width,
        // scale : scale, // effect of global size on graph objects
        // labelAlignment: 'center', // linkurous only and not compatible with drag node
        sideMargin: 1,
        maxNodeSize: maxNodeSize,
        minNodeSize: 5,
        minEdgeSize: 1,
        maxEdgeSize: maxNodeSize*1.5,

        // minArrowSize: 15,
        // maxArrowSize: 20,
        borderSize: 2,
        outerBorderSize: 3, // stroke size of active nodes
        defaultNodeColor: "#FFF",
        defaultNodeBorderColor: '#000',
        defaultNodeOuterBorderColor: 'rgb(236, 81, 72)', // stroke color of active nodes
        // enableEdgeHovering: true, // bad for memory
        zoomingRatio: 1.3,
        mouseWheelEnabled: false,
        edgeHoverColor: 'edge',
        defaultEdgeHoverColor: '#000',
        edgeHoverSizeRatio: 1,
        edgeHoverExtremities: true,
        doubleClickEnabled: false, // utilisé pour la suppression
      }
    });
    var els = this.canvas.getElementsByClassName('restore');
    if (els.length) {
      this.gravBut = els[0];
      els[0].net = this;
      els[0].onclick = function() {
        this.net.stop(); // stop force and restore button
        this.net.sigma.graph.clear();
        this.net.sigma.graph.read(this.net.odata);
        this.net.sigma.refresh();
      }
    }
    var els = this.canvas.getElementsByClassName('grav');
    if (els.length) {
      this.gravBut = els[0];
      this.gravBut.net = this;
      this.gravBut.onclick = this.grav;
    }
    var els = this.canvas.getElementsByClassName('colors');
    if (els.length) {
      els[0].net = this;
      els[0].onclick = function() {
        var bw = this.net.sigma.settings( 'bw' );
        if (!bw) {
          this.innerHTML = '🌈';
          this.net.sigma.settings( 'bw', true );
        }
        else {
          this.innerHTML = '◐';
          this.net.sigma.settings( 'bw', false );
        }
        this.net.sigma.refresh();
      };
    }
    var els = this.canvas.getElementsByClassName( 'zoomin' );
    if (els.length) {
      els[0].net = this;
      els[0].onclick = function() {
        var c = this.net.sigma.camera; c.goTo({ratio: c.ratio / c.settings('zoomingRatio')});
      };
    }
    var els = this.canvas.getElementsByClassName( 'zoomout' );
    if (els.length) {
      els[0].net = this;
      els[0].onclick = function() {
        var c = this.net.sigma.camera; c.goTo({ratio: c.ratio * c.settings('zoomingRatio')});
      };
    }
    var els = this.canvas.getElementsByClassName( 'turnleft' );
    if (els.length) {
      els[0].net = this;
      els[0].onclick = function() {
        var c = this.net.sigma.camera; c.goTo({ angle: c.angle+( Math.PI*15/180) });
      };
    }
    var els = this.canvas.getElementsByClassName( 'turnright' );
    if (els.length) {
      els[0].net = this;
      els[0].onclick = function() {
        var c = this.net.sigma.camera; c.goTo({ angle: c.angle-( Math.PI*22.5/180) });
      };
    }


    var els = this.canvas.getElementsByClassName( 'mix' );
    if (els.length) {
      this.mixBut = els[0];
      this.mixBut.net = this;
      this.mixBut.onclick = this.mix;
    }
    var els = this.canvas.getElementsByClassName( 'shot' );
    if (els.length) {
      els[0].net = this;
      els[0].onclick = function() {
        this.net.stop(); // stop force
        this.net.sigma.refresh();
        var s =  this.net.sigma;
        var size = prompt("Largeur de l’image (en px)", window.innerWidth);
        sigma.plugins.image(s, s.renderers[0], {
          download: true,
          margin: 50,
          size: size,
          clip: true,
          zoomRatio: 1,
          background: "#FFFFFF",
          labels: false,
        });
      };
    }

    // resizer
    var els = this.canvas.getElementsByClassName( 'resize' );
    if (els.length) {
      els[0].net = this;
      els[0].onmousedown = function(e) {
        this.net.stop();
        var html = document.documentElement;
        html.sigma = this.net.sigma; // give an handle to the sigma instance
        html.dragO = this.net.canvas;
        html.dragX = e.clientX;
        html.dragY = e.clientY;
        html.dragWidth = parseInt( document.defaultView.getComputedStyle( html.dragO ).width, 10 );
        html.dragHeight = parseInt( document.defaultView.getComputedStyle( html.dragO ).height, 10 );
        html.addEventListener( 'mousemove', Rolenet.doDrag, false );
        html.addEventListener( 'mouseup', Rolenet.stopDrag, false );
      };
    }

    this.sigma.bind( 'rightClickNode', function( e ) {
      e.data.renderer.graph.dropNode(e.data.node.id);
      e.target.refresh();
    });
    // Initialize the dragNodes plugin:
    sigma.plugins.dragNodes( this.sigma, this.sigma.renderers[0] );
    this.start();
  }
  Rolenet.prototype.start = function() {
    if (this.gravBut) this.gravBut.innerHTML = '◼';
    var pars = {
      // slowDown: 1,
      // adjustSizes: true, // avec iterationsPerRender, resserre trop le réseau
      // linLogMode: true, // oui avec gravité > 1
      gravity: 0.4, // <1 pour le Tartuffe
      // edgeWeightInfluence: 1, // demande iterationsPerRender, désorganise
      // outboundAttractionDistribution: true, // ?, même avec iterationsPerRender
      // barnesHutOptimize: true, // tartuffe instable
      // barnesHutTheta: 0.1,  // pas d’effet apparent sur si petit graphe
      scalingRatio: 2, // non, pas compris
      // strongGravityMode: true, // instable, nécessaire avec outboundAttractionDistribution
      startingIterations : 100,
      iterationsPerRender : 10, // important
    };
    if (window.Worker) {
      pars.worker = true;
    }
    if (this.workerUrl) {
      pars.workerUrl = this.workerUrl;
    }
    this.sigma.startForceAtlas2( pars );
    var dramanet = this;
    setTimeout(function() { dramanet.stop();}, 3000)
  };
  Rolenet.prototype.stop = function() {
    this.sigma.killForceAtlas2();
    if (this.gravBut) this.gravBut.innerHTML = '►';
  };
  Rolenet.prototype.grav = function() {
    if ((this.net.sigma.supervisor || {}).running) {
      this.net.sigma.killForceAtlas2();
      this.innerHTML = '►';
    }
    else {
      this.innerHTML = '◼';
      this.net.start();
    }
    return false;
  };
  Rolenet.prototype.mix = function() {
    this.net.sigma.killForceAtlas2();
    if (this.net.gravBut) this.net.gravBut.innerHTML = '►';
    for (var i=0; i < this.net.sigma.graph.nodes().length; i++) {
      this.net.sigma.graph.nodes()[i].x = Math.random()*10;
      this.net.sigma.graph.nodes()[i].y = Math.random()*10;
    }
    this.net.sigma.refresh();
    // this.net.start();
    return false;
  };
  // global static
  Rolenet.doDrag = function( e ) {
    this.dragO.style.width = ( this.dragWidth + e.clientX - this.dragX ) + 'px';
    this.dragO.style.height = ( this.dragHeight + e.clientY - this.dragY ) + 'px';
  };
  Rolenet.stopDrag = function( e ) {
    var height = this.dragO.offsetHeight;
    var width = this.dragO.offsetWidth;

    this.removeEventListener( 'mousemove', Rolenet.doDrag, false );
    this.removeEventListener( 'mouseup', Rolenet.stopDrag, false );
    this.sigma.settings( 'height', height );
    this.sigma.settings( 'width', width );
    // var scale = Math.max( height, 150) / 500;
    // this.sigma.settings( 'scale', scale );
    this.sigma.refresh();
  };

})();
