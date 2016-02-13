
// failed attempt for sigma autoloader
;(function() {
  'use strict';
  if ("onhashchange" in window) {
    window.addEventListener("hashchange", function() {
      window.scrollBy(0, -60);
    }, false);

  };
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
      x1: x - size * 5,
      y1: y,
      x2: x,
      y2: y + size * 5
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
    var prefix = settings('prefix') || '';

    context.fillStyle = node.color || settings('defaultNodeColor');
    context.beginPath();
    context.arc(
      node[prefix + 'x'],
      node[prefix + 'y'],
      node[prefix + 'size'],
      0,
      Math.PI * 2,
      true
    );

    context.closePath();
    context.fill();
    context.strokeStyle = "#000";
    context.lineWidth = 1;
    context.stroke();
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
    context.save();
    var fontSize,
        prefix = settings('prefix') || '',
        size = node[prefix + 'size'];

    if (size < settings('labelThreshold'))
      return;

    if (!node.label || typeof node.label !== 'string')
      return;

    fontSize = (settings('labelSize') === 'fixed') ?
      settings('defaultLabelSize') :
      settings('labelSizeRatio') * size;

    context.font = fontSize + 'px ' + settings('font');

    var x = Math.round(node[prefix + 'x'] + size + 3);
    var y = Math.round(node[prefix + 'y'] + fontSize / 3);
    var width = Math.round(context.measureText(node.label).width);
    var height = parseInt(context.font, 10);
    // bg color
    context.fillStyle = 'rgba(255, 255, 255, 0.5)';
    context.fillRect(x-2, y - fontSize + 3, width+4, height);
    // text color
    context.fillStyle = (settings('labelColor') === 'node') ?
      (node.color || settings('defaultNodeColor')) :
      settings('defaultLabelColor');
    context.fillText( node.label, x, y);
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
        tSize = target[prefix + 'size'],
        sSize = target[prefix + 'size'],
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
        vY;

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
      context.strokeStyle = "#888";
      context.lineWidth = size / 2;
      context.beginPath();
      context.moveTo(sX, sY);
      cp = sigma.utils.dramaSelf(sX, sY, tSize);
      context.bezierCurveTo(cp.x1, cp.y1, cp.x2, cp.y2, tX, tY);
      context.stroke();
    }
    // target edge, arrow
    else {


      var aSize = Math.max(size * 1.5, settings('minArrowSize'));
      // distance from source to target
      var d = Math.sqrt(Math.pow(tX - sX, 2) + Math.pow(tY - sY, 2));
      // diff for arrow line
      var dX = (tY - sY) * (size/2) / d;
      var dY = -(tX - sX) * (size/2) / d;
      var d2X = (tY - sY) * (2+size/2) / d;
      var d2Y = -(tX - sX) * (2+size/2) / d;
      // base of arrowhead
      var bX = sX + (tX - sX) * (d - tSize - aSize) / d;
      var bY = sY + (tY - sY) * (d - tSize - aSize) / d;
      // target point of arrow
      var aX = sX + (tX - sX) * (d - tSize) / d;
      var aY = sY + (tY - sY) * (d - tSize) / d;
      var a2X = sX + (tX - sX) * (d - tSize + 2) / d;
      var a2Y = sY + (tY - sY) * (d - tSize + 2) / d;
      // diff for arrowhead base
      var dbX = (tY - sY) * (size*1.5) / d;
      var dbY = (tX - sX) * (size*1.5) / d;

      // start line from outside the source node
      oX = sX + (tX - sX) * (0.9*source[prefix + 'size'] / d),
      oY =  sY + (tY - sY) * (0.9*source[prefix + 'size'] / d);
      /*
                  3
              1---2 \
        source       4 target
              7---6 /
                  5
      */


      context.beginPath();
      context.globalCompositeOperation='destination-over';
      context.moveTo(sX + dX, sY + dY); // 1
      context.lineTo(bX + dX, bY + dY); // 2
      context.lineTo(bX + dX*2, bY + dY*2); // 3
      context.lineTo(aX , aY ); // 4
      context.lineTo(bX - dX*2, bY - dY*2); // 5
      context.lineTo(bX - dX, bY - dY); // 6
      context.lineTo(sX - dX, sY - dY ); // 7
      context.lineWidth = 1;
      context.strokeStyle = '#BBB';
      context.stroke();
      context.closePath();
      context.fillStyle = 'rgba(128, 128, 128, 0.1)';
      context.fill();

      context.globalCompositeOperation='source-over';
      context.beginPath();
      context.lineWidth = 1;
      context.strokeStyle = '#000000';
      context.moveTo(bX + dX, bY + dY); // 2
      context.lineTo(bX + dX*2, bY + dY*2); // 3
      context.stroke();
      context.closePath();

      context.beginPath();
      context.lineWidth = 1;
      context.strokeStyle = '#FFF';
      context.moveTo(bX + (d2X*2), bY + (d2Y*2) ); // 3
      context.lineTo(a2X , a2Y ); // 4
      context.lineTo(bX - (d2X*2), bY - (d2Y*2)); // 5
      context.stroke();
      context.closePath();
      /*
      context.beginPath();
      context.lineWidth = 0.5;
      context.strokeStyle = '#000';
      context.moveTo(bX + dX*2, bY + dY*2); // 3
      context.lineTo(aX , aY ); // 4
      context.lineTo(bX - dX*2, bY - dY*2); // 5
      context.stroke();
      context.closePath();
      */
      context.beginPath();
      context.lineWidth = 1;
      context.strokeStyle = '#000000';
      context.moveTo(bX - dX*2, bY - dY*2); // 5
      context.lineTo(bX - dX, bY - dY); // 6
      context.stroke();
      context.closePath();

    }
  };



  window.Rolenet = function (canvas, graph, workerUrl) {
    this.workerUrl = workerUrl;
    this.canvas = document.getElementById(canvas);
    this.odata = graph;
    this.sigma = new sigma({
      graph: graph,
      renderer: {
        container: this.canvas,
        type: 'canvas'
      },
      settings: {
        defaultEdgeColor: "rgba(230, 240, 240, 0.8)",
        defaultNodeColor: "rgba(230, 230, 230, 0.7)",
        edgeColor: "default",
        drawLabels: true,
        defaultLabelSize: 18,
        // font: 'arial',
        /* marche mais trop grand avec les commentaires
        labelSize: "proportional",
        labelSizeRatio: 1,
        */
        // labelAlignment: 'center', // linkurous only and not compatible with drag node
        sideMargin: 1,
        maxNodeSize: 30,
        minNodeSize: 8,
        minEdgeSize: 1,
        maxEdgeSize: 30,
        minArrowSize: 15,
        maxArrowSize: 20,
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
    var els = this.canvas.getElementsByClassName('zoomin');
    if (els.length) {
      els[0].net = this;
      els[0].onclick = function() {
        var c = this.net.sigma.camera; c.goTo({ratio: c.ratio / c.settings('zoomingRatio')});
      };
    }
    var els = this.canvas.getElementsByClassName('zoomout');
    if (els.length) {
      els[0].net = this;
      els[0].onclick = function() {
        var c = this.net.sigma.camera; c.goTo({ratio: c.ratio * c.settings('zoomingRatio')});
      };
    }


    var els = this.canvas.getElementsByClassName('mix');
    if (els.length) {
      this.mixBut = els[0];
      this.mixBut.net = this;
      this.mixBut.onclick = this.mix;
    }
    var els = this.canvas.getElementsByClassName('shot');
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
          labels: false
        });
      };
    }

    // resizer
    var els = this.canvas.getElementsByClassName('resize');
    if (els.length) {
      els[0].net = this;
      els[0].onmousedown = function(e) {
        this.net.stop();
        var html = document.documentElement;
        html.sigma = this.net.sigma; // give an handle to the sigma instance
        html.dragO = this.net.canvas;
        html.dragX = e.clientX;
        html.dragY = e.clientY;
        html.dragWidth = parseInt(document.defaultView.getComputedStyle(html.dragO).width, 10);
        html.dragHeight = parseInt(document.defaultView.getComputedStyle(html.dragO).height, 10);
        html.addEventListener('mousemove', Rolenet.doDrag, false);
        html.addEventListener('mouseup', Rolenet.stopDrag, false);
      };
    }


    this.sigma.bind('overNode', function(e) {
      // attention, n’écrire qu’une fois
      if (!e.data.node._label && e.data.node.title) {
        e.data.node._label = e.data.node.label;
        e.data.node.label = e.data.node.label + ', ' + e.data.node.title;
        e.target.render();
      }
    });
    this.sigma.bind('rightClickNode', function(e) {
      e.data.renderer.graph.dropNode(e.data.node.id);
      e.target.refresh();
    });
    this.sigma.bind('outNode', function(e) {
      if (e.data.node._label) {
        e.data.node.label = e.data.node._label;
        e.data.node._label = null;
        e.target.render();
      }
    });
    // Initialize the dragNodes plugin:
    sigma.plugins.dragNodes(this.sigma, this.sigma.renderers[0]);
    this.start();
  }
  Rolenet.prototype.start = function() {
    if (this.gravBut) this.gravBut.innerHTML = '◼';
    var pars = {
      // slowDown: 1,
      // adjustSizes: true, // non, même avec iterationsPerRender
      linLogMode: true, // oui avec gravité > 1
      gravity: 1.2, //
      // edgeWeightInfluence: 1.1, // bof, même avec iterationsPerRender
      // outboundAttractionDistribution: true, // ?, même avec iterationsPerRender
      barnesHutOptimize: false, // ?
      // barnesHutTheta: 0.1,  // pas d’effet apparent sur si petit graphe
      // scalingRatio: 2, // non
      // outboundAttractionDistribution: true, // pas avec beaucoup de petits rôles
      // strongGravityMode: true, // instable, nécessaire avec outboundAttractionDistribution
      iterationsPerRender : 15, // important
    };
    if (window.Worker) {
      pars.worker = true;
    }
    if (this.workerUrl) {
      pars.workerUrl = this.workerUrl;
    }
    this.sigma.startForceAtlas2(pars);
    var dramanet = this;
    setTimeout(function() { dramanet.stop();}, 5000)
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
    this.net.start();
    return false;
  };
  // global static
  Rolenet.doDrag = function(e) {
    this.dragO.style.width = (this.dragWidth + e.clientX - this.dragX) + 'px';
    this.dragO.style.height = (this.dragHeight + e.clientY - this.dragY) + 'px';
  };
  Rolenet.stopDrag = function(e) {
    this.removeEventListener('mousemove', Rolenet.doDrag, false);
    this.removeEventListener('mouseup', Rolenet.stopDrag, false);
    this.sigma.refresh();
  };

})();
