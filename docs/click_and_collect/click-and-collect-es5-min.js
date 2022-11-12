"use strict";var _slicedToArray=function(){return function(e,t){if(Array.isArray(e))return e;if(Symbol.iterator in Object(e))return function(e,t){var a=[],n=!0,i=!1,s=void 0;try{for(var l,r=e[Symbol.iterator]();!(n=(l=r.next()).done)&&(a.push(l.value),!t||a.length!==t);n=!0);}catch(e){i=!0,s=e}finally{try{!n&&r.return&&r.return()}finally{if(i)throw s}}return a}(e,t);throw new TypeError("Invalid attempt to destructure non-iterable instance")}}(),_createClass=function(){function e(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}return function(t,a,n){return a&&e(t.prototype,a),n&&e(t,n),t}}();function _possibleConstructorReturn(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function _inherits(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}var ClickAndCollect=function(){function e(t){_classCallCheck(this,e),this.settings=t,this.element=document.getElementById("cnc-container"),this.resultsContainer=document.getElementById("cnc-results-container"),this.mapContainer=document.getElementById("cnc-map-container"),this.messages={},this.mapFocus=null,this.settings.enableMap&&window.mapIsReady&&this.mapReady()}return _createClass(e,[{key:"addMessage",value:function(e,t,a){if(!this.messages[e]){var n=new Message(t,a);this.messages[e]=n,this.resultsContainer.appendChild(n.getElement())}}},{key:"removeMessage",value:function(e){this.messages[e]&&(this.messages[e].getElement().outerHTML="",delete this.messages[e])}},{key:"clearErrors",value:function(){var e=!0,t=!1,a=void 0;try{for(var n,i=Object.entries(this.messages)[Symbol.iterator]();!(e=(n=i.next()).done);e=!0){var s=_slicedToArray(n.value,2),l=s[0];s[1].isError&&this.removeMessage(l)}}catch(e){t=!0,a=e}finally{try{!e&&i.return&&i.return()}finally{if(t)throw a}}}},{key:"addAddressSearch",value:function(){this.search=new AddressSearch(this),this.mapContainer.appendChild(this.search.getElement())}},{key:"addMap",value:function(){this.gmap=new GMap(this),this.mapContainer.appendChild(this.gmap.getElement()),this.gmap.initMap()}},{key:"mapReady",value:function(){this.addAddressSearch(),this.addMap()}},{key:"searchAddress",value:function(e){var t=new google.maps.Geocoder,a=this;t.geocode({address:e},function(t,n){n==google.maps.GeocoderStatus.OK?(a.removeMessage("addressNotFound"),a.mapFocus=t[0].geometry.location,a.results.setLocation(a.mapFocus.lat(),a.mapFocus.lng()),a.results.update(),a.gmap.show()):(a.results.setLocation(null,null),a.addMessage("addressNotFound",e+" not found.",!0))})}},{key:"updateMap",value:function(){this.gmap.searchLocationsNear(this.mapFocus,this.results.getAllOutlets())}}]),e}(),ClickAndCollectCart=function(e){function t(e){_classCallCheck(this,t);var a=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return a.mode=null,a.modeButtons={},a.outletContactFields={},a.addModeButtons(["home","store"]),a.addOutletContactFields(["address1","address2","address3","suburb","state","postcode","phone","email"]),a.addOutletNameField(),a.addOutletNumberField(),a.addResults(),a.addCartUpdateListener(),a.setMode("home"),a.updateCollectionAbility(),a.settings.enableMap||a.results.update(),a}return _inherits(t,ClickAndCollect),_createClass(t,[{key:"addModeButtons",value:function(e){var t=!0,a=!1,n=void 0;try{for(var i,s=e[Symbol.iterator]();!(t=(i=s.next()).done);t=!0){var l=i.value,r=new ModeButton(this,l);this.modeButtons[l]=r,this.element.appendChild(r.getElement()),this.element.appendChild(r.getLabel())}}catch(e){a=!0,n=e}finally{try{!t&&s.return&&s.return()}finally{if(a)throw n}}var o=document.createElement("input");o.id="cnc-mode-input",o.type="hidden",o.name="attributes[cnc-fulfilment-method]",o.value="home",this.element.appendChild(o),this.modeInput=o}},{key:"addOutletContactFields",value:function(e){var t=!0,a=!1,n=void 0;try{for(var i,s=e[Symbol.iterator]();!(t=(i=s.next()).done);t=!0){var l=i.value,r=new OutletContactField(l);this.outletContactFields[l]=r,this.element.appendChild(r.getElement())}}catch(e){a=!0,n=e}finally{try{!t&&s.return&&s.return()}finally{if(a)throw n}}}},{key:"addOutletNumberField",value:function(){var e=new OutletContactField("number","cnc-outlet","attributes[cnc-outlet]");this.outletNumberField=e,this.element.appendChild(e.getElement())}},{key:"addOutletNameField",value:function(){var e=new OutletContactField("name","cnc-store-name","attributes[cnc-store-name]");this.outletNameField=e,this.element.appendChild(e.getElement())}},{key:"addResults",value:function(){this.results=new CartResults(this,this.settings.apiPrefix,this.settings.subdomain),this.resultsContainer.appendChild(this.results.getElement())}},{key:"getMode",value:function(){return this.mode}},{key:"setMode",value:function(e){if(e!==this.mode){this.clearErrors(),this.mode=e,this.modeInput.value=e;var t=!0,a=!1,n=void 0;try{for(var i,s=Object.entries(this.modeButtons)[Symbol.iterator]();!(t=(i=s.next()).done);t=!0){var l=_slicedToArray(i.value,2),r=(l[0],l[1]);r.getValue()===e&&r.select(),r.updateDisplay()}}catch(e){a=!0,n=e}finally{try{!t&&s.return&&s.return()}finally{if(a)throw n}}if("home"===this.mode){this.mapContainer&&(this.mapContainer.style.display="none"),this.results.hide(),this.results.selectNone();var o=!0,c=!1,u=void 0;try{for(var d,h=Object.entries(this.outletContactFields)[Symbol.iterator]();!(o=(d=h.next()).done);o=!0){var m=_slicedToArray(d.value,2);m[0];m[1].clear()}}catch(e){c=!0,u=e}finally{try{!o&&h.return&&h.return()}finally{if(c)throw u}}this.outletNameField.clear(),this.addMessage("home","You will be able to select a home delivery method in the checkout.",!1)}else"store"===this.mode&&(this.mapContainer&&(this.mapContainer.style.display=""),this.results.show(),this.removeMessage("home"),this.results.selectFirst(),this.updateContactFields());this.updateCheckoutButton()}}},{key:"updateCollectionAbility",value:function(){this.results.doOutletsExist()||this.settings.enableMap?(this.modeButtons.store.show(),this.removeMessage("noLocations")):(this.setMode("home"),this.modeButtons.store.hide(),this.addMessage("noLocations","No locations available for store pickup.",!0))}},{key:"updateContactFields",value:function(){var e=this.results.getSelectedOutlet();if(e){var t=!0,a=!1,n=void 0;try{for(var i,s=Object.entries(this.outletContactFields)[Symbol.iterator]();!(t=(i=s.next()).done);t=!0){var l=_slicedToArray(i.value,2),r=l[0];l[1].setValue(e.getContact()[r])}}catch(e){a=!0,n=e}finally{try{!t&&s.return&&s.return()}finally{if(a)throw n}}this.outletNameField.setValue(e.getName()),this.outletNumberField.setValue(e.getExternalId())}}},{key:"updateCheckoutButton",value:function(){var e=document.querySelector('button[name="checkout"]')||document.querySelector('input[name="checkout"]');e&&(this.checkoutButton=e,"store"!==this.mode||this.results.areOutletsAvailable()?(e.disabled=!1,this.removeMessage("pleaseUpdateCart"),this.removeMessage("pleaseSearchAgain")):(e.disabled=!0,this.results.isLoaded()&&(this.settings.enableMap&&this.results.count()<1?(this.removeMessage("pleaseUpdateCart"),this.addMessage("pleaseSearchAgain","Sorry, no pickup locations are available in this area. Please search again or choose Home Delivery.",!0)):(this.removeMessage("pleaseSearchAgain"),this.addMessage("pleaseUpdateCart","Sorry, no pickup locations are available for all items in your cart. Please remove items from your cart or choose Home Delivery.",!0)))))}},{key:"addCartUpdateListener",value:function(){var e=this,t=document.getElementById("CartProducts");t&&t.addEventListener("DOMSubtreeModified",function(t){e.results.isLoaded()&&e.results.update()})}}]),t}(),ClickAndCollectProduct=function(e){function t(e,a){_classCallCheck(this,t);var n=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.initialVariantId=a,n.addCheckStockButton(),n.addResults(),n.addVariantEventListener(),n.mapContainer&&(n.mapContainer.style.display="none"),n}return _inherits(t,ClickAndCollect),_createClass(t,[{key:"addCheckStockButton",value:function(){var e=new CheckStockButton(this);this.element.appendChild(e.getElement())}},{key:"addResults",value:function(){var e=new ProductResults(this,this.settings.apiPrefix,this.settings.subdomain,this.initialVariantId);this.results=e,this.resultsContainer.appendChild(this.results.getElement())}},{key:"addVariantEventListener",value:function(){var e=this,t=document.querySelector('form[action="/cart/add"]');t&&t.addEventListener("click",function(t){e.results.isLoaded()&&(e.results.unload(),e.clearErrors(),e.settings.enableMap&&(e.mapContainer.style.display="none",e.gmap.hide()))})}},{key:"reveal",value:function(){this.settings.enableMap?this.mapContainer.style.display="":this.results.fetch()}},{key:"resultsLoaded",value:function(){this.settings.enableMap&&this.results.count()<1?this.addMessage("pleaseSearchAgain","Sorry, no pickup locations are available in this area.",!0):this.removeMessage("pleaseSearchAgain")}}]),t}(),Renderable=function(){function e(){_classCallCheck(this,e)}return _createClass(e,[{key:"getElement",value:function(){return this.element}},{key:"show",value:function(){this.element.style.display="",this.label&&(this.label.style.display="")}},{key:"hide",value:function(){this.element.style.display="none",this.label&&(this.label.style.display="none")}}]),e}(),AddressSearch=function(e){function t(e){_classCallCheck(this,t);var a=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return a.cnc=e,a.createElement(),a}return _inherits(t,Renderable),_createClass(t,[{key:"createElement",value:function(){var e=this;this.element=document.createElement("div"),this.element.classList.add("cnc-address-search"),this.element.id="cnc-address-search",this.label=document.createElement("p"),this.label.innerHTML="Search for a location to pick up your order:",this.element.appendChild(this.label),this.input=document.createElement("input"),this.input.type="text",this.input.classList.add("cnc-address-search-input"),this.input.placeholder="Enter your postcode...",this.element.appendChild(this.input),this.button=document.createElement("button"),this.button.type="button",this.button.classList.add("cnc-address-search-button"),this.button.innerHTML='<i class="fas fa-search"></i><span class="cnc-address-search-label">Search</span>',this.element.appendChild(this.button),this.button.addEventListener("click",function(t){e.cnc.searchAddress(e.input.value)});var t=this;this.input.addEventListener("keyup",function(e){if(13===e.keyCode)return e.preventDefault(),t.button.click(),!1}),this.input.addEventListener("keydown",function(e){if(13===e.keyCode)return e.preventDefault(),!1})}}]),t}(),GMap=function(e){function t(e){_classCallCheck(this,t);var a=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return a.cnc=e,a.gmap,a.markers=[],a.infoWindow,a.locationSelect,a.mapInitialised=!1,a.createElement(),a}return _inherits(t,Renderable),_createClass(t,[{key:"createElement",value:function(){this.element=document.createElement("div"),this.element.id="cnc-map",this.hide()}},{key:"initMap",value:function(){this.gmap=new google.maps.Map(this.element,{center:{lat:-33.863276,lng:151.107977},zoom:11,zoomControl:!0,mapTypeId:"roadmap"}),this.infoWindow=new google.maps.InfoWindow,this.mapInitialised=!0}},{key:"clearLocations",value:function(){this.infoWindow.close();for(var e=0;e<this.markers.length;e++)this.markers[e].setMap(null);this.markers.length=0}},{key:"searchLocationsNear",value:function(e,t){this.clearLocations();var a=new google.maps.LatLngBounds,n=!0,i=!1,s=void 0;try{for(var l,r=Object.entries(t)[Symbol.iterator]();!(n=(l=r.next()).done);n=!0){var o=_slicedToArray(l.value,2),c=o[0],u=o[1],d=(a=new google.maps.LatLngBounds,u.getAddressHtml()),h=new google.maps.LatLng(parseFloat(u.getContact().latitude),parseFloat(u.getContact().longitude));this.createMarker(h,c,d,u.data.distance),a.extend(h)}}catch(e){i=!0,s=e}finally{try{!n&&r.return&&r.return()}finally{if(i)throw s}}if(t.length>=1){this.gmap.fitBounds(a);var m=this.gmap.getZoom();this.gmap.setZoom(m>10?10:m)}else this.gmap.setCenter(e)}},{key:"createMarker",value:function(e,t,a,n){var i="<b>"+t+"</b><br>";n&&(i+=n+"km<br>"),i+=a;var s=new google.maps.Marker({map:this.gmap,position:e}),l=this;google.maps.event.addListener(s,"click",function(){l.infoWindow.setContent(i),l.infoWindow.open(l.gmap,s)}),this.markers.push(s)}}]),t}(),ModeButton=function(e){function t(e,a){_classCallCheck(this,t);var n=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return n.cnc=e,n.value=a,n.createElement(),n.createLabel(),n.element.addEventListener("change",function(e){n.cnc.setMode(n.element.value)}),n.updateDisplay(),n}return _inherits(t,Renderable),_createClass(t,[{key:"select",value:function(){this.element.checked||(this.element.checked=!0)}},{key:"updateDisplay",value:function(){this.element.checked?this.label.classList.add("btn--secondary"):this.label.classList.remove("btn--secondary")}},{key:"createElement",value:function(){this.element=document.createElement("input"),this.element.id="cnc-"+this.value+"-radio",this.element.type="radio",this.element.value=this.value,this.element.name="cnc-mode-radio"}},{key:"createLabel",value:function(){this.label=document.createElement("label"),this.label.setAttribute("for","cnc-"+this.value+"-radio"),this.label.classList.add("btn"),"home"===this.value?this.label.innerHTML='<i class="fas fa-globe-americas"></i><br>Home Delivery':"store"===this.value&&(this.label.style.display="none",this.label.innerHTML='<i class="fas fa-store"></i><br>Store Pickup')}},{key:"getLabel",value:function(){return this.label}},{key:"getValue",value:function(){return this.element.value}}]),t}(),CheckStockButton=function(e){function t(e){_classCallCheck(this,t);var a=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return a.cncProduct=e,a.createElement(),a.element.addEventListener("click",function(e){var t=document.querySelector('form[action="/cart/add"] button[type="submit"]');t&&t.disabled?a.cncProduct.addMessage("productUnavailable","Product variant is unavailable.",!0):(a.cncProduct.removeMessage("productUnavailable"),a.cncProduct.reveal())}),a}return _inherits(t,Renderable),_createClass(t,[{key:"createElement",value:function(){this.element=document.createElement("button"),this.element.innerHTML="Check stock in store",this.element.classList.add("btn")}}]),t}(),Outlet=function(e){function t(e,a,n){_classCallCheck(this,t);var i=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return i.cnc=e,i.data=a,i.createAvailabilityTable(n),i.createElement(),i.createLabel(),i.element.addEventListener("change",function(t){e.updateContactFields()}),i}return _inherits(t,Renderable),_createClass(t,[{key:"getAvailability",value:function(){return this.availabilityTable&&this.availabilityTable.isOutletAvailable()}},{key:"getAvailabilityTable",value:function(){return this.availabilityTable}},{key:"createAvailabilityTable",value:function(e){this.availabilityTable=new AvailabilityTable(e,this.data.stock,!0,this.cnc.settings.showQuantity)}},{key:"isSelected",value:function(){return this.element.checked}},{key:"select",value:function(){this.element.checked=!0}},{key:"deselect",value:function(){this.element.checked=!1}},{key:"getContact",value:function(){return this.data.contact}},{key:"getName",value:function(){return this.data.name}},{key:"getExternalId",value:function(){return this.data.external_id}},{key:"createElement",value:function(){this.element=document.createElement("input"),this.element.id="cnc-outlet-"+this.data.external_id,this.element.type="radio",this.element.name="cnc-outlet-element",this.element.value=this.data.external_id,this.element.disabled=!this.getAvailability(),this.element.classList.add("cnc-outlet")}},{key:"createLabel",value:function(){this.label=document.createElement("label"),this.label.setAttribute("for","cnc-outlet-"+this.data.external_id),this.label.classList.add("cnc-outlet-label");var e=document.createElement("div");e.classList.add("cnc-store-details"),e.insertAdjacentHTML("beforeend","<strong>"+this.data.name+"</strong><br>"),this.data.distance&&e.insertAdjacentHTML("beforeend",this.data.distance+"km<br>"),e.insertAdjacentHTML("beforeend",this.getAddressHtml()),this.label.appendChild(e),this.label.appendChild(this.getAvailabilityTable().getElement())}},{key:"getAddressHtml",value:function(){document.createElement("span").id="cnc-outlet-"+this.data.external_id+"-address";var e="";return this.data.contact.address1&&(e+=this.data.contact.address1+"<br>"),this.data.contact.address2&&(e+=this.data.contact.address2+"<br>"),this.data.contact.address3&&(e+=this.data.contact.address3+"<br>"),this.data.contact.suburb&&(e+=this.data.contact.suburb+" "),this.data.contact.state&&(e+=this.data.contact.state+" "),this.data.contact.postcode&&(e+=this.data.contact.postcode),e+="<br>",this.data.contact.phone&&(e+='Phone: <a href="tel:'+this.data.contact.phone+'">'+this.data.contact.phone+"</a><br>"),this.data.contact.email&&(e+='Email: <a href="mailto:'+this.data.contact.email+'">'+this.data.contact.email+"</a>"),e}},{key:"getLabel",value:function(){return this.label}}]),t}(),ProductOutlet=function(e){function t(e,a,n){return _classCallCheck(this,t),_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e,a,n))}return _inherits(t,Outlet),_createClass(t,[{key:"createAvailabilityTable",value:function(e){this.availabilityTable=new AvailabilityTable(e,this.data.stock,!1,this.cnc.settings.showQuantity)}},{key:"createElement",value:function(){this.element=document.createElement("div"),this.element.classList.add("cnc-outlet")}},{key:"createLabel",value:function(){this.label=document.createElement("div"),this.label.classList.add("cnc-outlet-label"),this.label.classList.add("cnc-product-outlet-label");var e=document.createElement("div");e.classList.add("cnc-store-details"),e.insertAdjacentHTML("beforeend","<strong>"+this.data.name+"</strong><br>"),e.insertAdjacentHTML("beforeend",this.getAddressHtml()),this.label.appendChild(e),this.label.appendChild(this.getAvailabilityTable().getElement())}}]),t}(),AvailabilityTable=function(e){function t(e,a,n,i){_classCallCheck(this,t);var s=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return s.products=[],s.showTable=n,s.showQuantity=i,s.updateProducts(e,a),s.createElement(),s}return _inherits(t,Renderable),_createClass(t,[{key:"updateProducts",value:function(e,t){if(e&&e.length){var a=!0,n=!1,i=void 0;try{for(var s,l=e[Symbol.iterator]();!(a=(s=l.next()).done);a=!0){var r=s.value;(v=Object.assign({},r)).outletStock=t[r.variant_id],this.products.push(v)}}catch(e){n=!0,i=e}finally{try{!a&&l.return&&l.return()}finally{if(n)throw i}}}else{var o=!0,c=!1,u=void 0;try{for(var d,h=Object.entries(t)[Symbol.iterator]();!(o=(d=h.next()).done);o=!0){var m=_slicedToArray(d.value,2),v={variant_id:m[0],outletStock:m[1],quantity:1};this.products.push(v)}}catch(e){c=!0,u=e}finally{try{!o&&h.return&&h.return()}finally{if(c)throw u}}}}},{key:"isOutletAvailable",value:function(){var e=!0,t=!1,a=void 0;try{for(var n,i=this.products[Symbol.iterator]();!(e=(n=i.next()).done);e=!0){var s=n.value;if(!this.isProductAvailable(s))return!1}}catch(e){t=!0,a=e}finally{try{!e&&i.return&&i.return()}finally{if(t)throw a}}return!0}},{key:"isProductAvailable",value:function(e){return e.outletStock>=e.quantity}},{key:"createElement",value:function(){this.showTable?this.createTableElement():this.createTextElement()}},{key:"createTableElement",value:function(){if(this.element=document.createElement("table"),this.element.classList.add("cnc-availability-table"),this.isOutletAvailable())var e='<tr><th colspan="3" class="cnc-heading-available">All items available</th></tr>';else e='<tr><th colspan="3" class="cnc-heading-unavailable">Some items are not available</th></tr>';var t=document.createElement("thead");t.insertAdjacentHTML("beforeend",e),t.insertAdjacentHTML("beforeend","<tr><th>Product</th><th>Qty</th><th>Available</th></tr>"),this.element.appendChild(t);var a=document.createElement("tbody"),n=!0,i=!1,s=void 0;try{for(var l,r=this.products[Symbol.iterator]();!(n=(l=r.next()).done);n=!0){var o=l.value;if(this.isProductAvailable(o))var c=o.title;else if(o.outletStock>0)c='<span class="strike">'+o.title+'</span> <span class="cnc-negative-text">(Insufficient stock)</span>';else c='<span class="strike">'+o.title+'</span> <span class="cnc-negative-text">(Out of stock)</span>';if(this.showQuantity)var u=this.isProductAvailable(o)?'<span class="cnc-positive-text"><strong>'+o.outletStock+"</strong></span>":'<span class="cnc-negative-text"><strong>'+o.outletStock+"</strong></span>";else u=this.isProductAvailable(o)?'<i class="fas fa-check-circle cnc-positive-text"></i>':'<i class="fas fa-times-circle cnc-negative-text"></i>';a.insertAdjacentHTML("beforeend","<tr><td>"+c+"</td><td>"+o.quantity+"</td><td>"+u+"</td></tr>")}}catch(e){i=!0,s=e}finally{try{!n&&r.return&&r.return()}finally{if(i)throw s}}this.element.appendChild(a)}},{key:"createTextElement",value:function(){this.element=document.createElement("p"),this.element.classList.add("cnc-heading-availability"),this.isOutletAvailable()?(this.showQuantity&&1===this.products.length?this.element.innerHTML=this.products[0].outletStock+" available":this.element.innerHTML="In stock",this.element.classList.add("cnc-heading-available")):(this.element.innerHTML="Out of stock",this.element.classList.add("cnc-heading-unavailable"))}}]),t}(),OutletContactField=function(e){function t(e,a,n){_classCallCheck(this,t);var i=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return i.name=e,i.createElement(a,n),i}return _inherits(t,Renderable),_createClass(t,[{key:"createElement",value:function(e,t){this.element=document.createElement("input"),this.element.type="hidden",this.element.classList.add("cnc-contact"),this.element.id=e||"cnc-contact-"+this.name,this.element.name=t||"attributes[cnc-store-"+this.name+"]",this.element.value=""}},{key:"getElement",value:function(){return this.element}},{key:"clear",value:function(){this.element.value=""}},{key:"setValue",value:function(e){this.element.value=e}}]),t}(),Message=function(e){function t(e,a){_classCallCheck(this,t);var n=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return n.createElement(),n.setValue(e),n.setIsError(a),n}return _inherits(t,Renderable),_createClass(t,[{key:"createElement",value:function(){this.element=document.createElement("div"),this.element.id="cnc-message",this.paragraph=document.createElement("p"),this.element.appendChild(this.paragraph)}},{key:"getValue",value:function(){return this.value}},{key:"setValue",value:function(e){this.value=e,this.paragraph.innerHTML=e}},{key:"clear",value:function(){this.setValue("")}},{key:"setIsError",value:function(e){this.isError=e,this.isError?this.element.classList.add("cnc-negative-text"):this.element.classList.remove("cnc-negative-text")}}]),t}(),Results=function(e){function t(e,a,n){_classCallCheck(this,t);var i=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return i.cnc=e,i.apiPrefix=a,i.subdomain=n,i.outlets={},i.loaded=!1,i.lastUpdate=0,i}return _inherits(t,Renderable),_createClass(t,[{key:"update",value:function(){}},{key:"fetch",value:function(){}},{key:"createElement",value:function(){this.element=document.createElement("div"),this.element.id="cnc-results",this.element.style.display="none"}},{key:"createList",value:function(){this.list=document.createElement("ul"),this.list.id="cnc-outlets",this.list.classList.add("cart-attribute__field"),this.element.appendChild(this.list)}},{key:"setLocation",value:function(e,t){this.latitude=e,this.longitude=t}},{key:"getAllOutlets",value:function(){return this.outlets}},{key:"count",value:function(){return Object.keys(this.outlets).length}},{key:"unload",value:function(){for(this.outlets=[];this.list.firstChild;)this.list.removeChild(this.list.firstChild)}},{key:"renderOutlet",value:function(e){var t=document.createElement("li");t.appendChild(e.getElement()),t.appendChild(e.getLabel()),this.list.appendChild(t)}},{key:"isLoaded",value:function(){return this.loaded}},{key:"doOutletsExist",value:function(){return Object.keys(this.outlets).length>0}},{key:"areOutletsAvailable",value:function(){var e=!0,t=!1,a=void 0;try{for(var n,i=Object.entries(this.outlets)[Symbol.iterator]();!(e=(n=i.next()).done);e=!0){var s=_slicedToArray(n.value,2);s[0];if(s[1].getAvailability())return!0}}catch(e){t=!0,a=e}finally{try{!e&&i.return&&i.return()}finally{if(t)throw a}}}}]),t}(),CartResults=function(e){function t(e,a,n){_classCallCheck(this,t);var i=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e,a,n));return i.createElement(),i.createInfo(),i.createList(),i.variantIds=[],i.cartItems={},i}return _inherits(t,Results),_createClass(t,[{key:"createInfo",value:function(){this.info=document.createElement("p"),this.info.id="cnc-info",this.element.appendChild(this.info)}},{key:"setInfo",value:function(e){this.info.innerHTML=e}},{key:"selectNone",value:function(){var e=!0,t=!1,a=void 0;try{for(var n,i=Object.entries(this.outlets)[Symbol.iterator]();!(e=(n=i.next()).done);e=!0){var s=_slicedToArray(n.value,2);s[0];s[1].deselect(),this.cnc.updateContactFields()}}catch(e){t=!0,a=e}finally{try{!e&&i.return&&i.return()}finally{if(t)throw a}}}},{key:"selectFirst",value:function(){var e=!0,t=!1,a=void 0;try{for(var n,i=Object.entries(this.outlets)[Symbol.iterator]();!(e=(n=i.next()).done);e=!0){var s=_slicedToArray(n.value,2),l=(s[0],s[1]);if(l.getAvailability())return l.select(),void this.cnc.updateContactFields()}}catch(e){t=!0,a=e}finally{try{!e&&i.return&&i.return()}finally{if(t)throw a}}}},{key:"getSelectedOutlet",value:function(){var e=!0,t=!1,a=void 0;try{for(var n,i=Object.entries(this.outlets)[Symbol.iterator]();!(e=(n=i.next()).done);e=!0){var s=_slicedToArray(n.value,2),l=(s[0],s[1]);if(l.isSelected())return l}}catch(e){t=!0,a=e}finally{try{!e&&i.return&&i.return()}finally{if(t)throw a}}}},{key:"update",value:function(){if(!(Date.now()-this.lastUpdate<1e3)){this.lastUpdate=Date.now();var e=this;Shopify.getCart(function(t){var a=[];t.items.forEach(function(e){a.push(e.variant_id)}),e.cartItems=t.items,e.variantIds=a,e.fetch()})}}},{key:"fetch",value:function(e){function t(){return e.apply(this,arguments)}return t.toString=function(){return e.toString()},t}(function(){this.unload();var e=this,t="https://"+this.apiPrefix+"/api/shopify_stores/"+this.subdomain+"/outlets?variant_ids="+this.variantIds.join(),a=!0,n=!1,i=void 0;try{for(var s,l=this.cartItems[Symbol.iterator]();!(a=(s=l.next()).done);a=!0){var r=s.value;t+="&qty-"+r.variant_id+"="+r.quantity}}catch(e){n=!0,i=e}finally{try{!a&&l.return&&l.return()}finally{if(n)throw i}}this.latitude&&this.longitude&&(t+="&latitude="+this.latitude+"&longitude="+this.longitude),fetch(t).then(function(e){return e.json()}).then(function(t){var a=!0,n=!1,i=void 0;try{for(var s,l=t.outlets[Symbol.iterator]();!(a=(s=l.next()).done);a=!0){var r=s.value,o=new Outlet(e.cnc,r,e.cartItems);e.outlets[o.getName()]=o,e.renderOutlet(o)}}catch(e){n=!0,i=e}finally{try{!a&&l.return&&l.return()}finally{if(n)throw i}}t.outlets.length?e.setInfo("Please choose a pickup location:"):e.setInfo(""),e.loaded=!0,e.cnc.updateCollectionAbility(),e.cnc.updateCheckoutButton(),e.cnc.settings.enableMap&&e.cnc.updateMap(),"store"===e.cnc.getMode()&&e.selectFirst()})})}]),t}(),ProductResults=function(e){function t(e,a,n,i){_classCallCheck(this,t);var s=_possibleConstructorReturn(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e,a,n));return s.createElement(),s.createList(),s.initialVariantId=i,s}return _inherits(t,Results),_createClass(t,[{key:"createElement",value:function(){this.element=document.createElement("div"),this.element.id="cnc-results"}},{key:"update",value:function(){this.fetch()}},{key:"fetch",value:function(e){function t(){return e.apply(this,arguments)}return t.toString=function(){return e.toString()},t}(function(){this.unload();var e=this,t=this.findGetParameter("variant");t||(t=this.initialVariantId);var a="https://"+this.apiPrefix+"/api/shopify_stores/"+this.subdomain+"/outlets?variant_ids="+t+"&qty-"+t+"=1";this.latitude&&this.longitude&&(a+="&latitude="+this.latitude+"&longitude="+this.longitude),fetch(a).then(function(e){return e.json()}).then(function(t){var a=!0,n=!1,i=void 0;try{for(var s,l=t.outlets[Symbol.iterator]();!(a=(s=l.next()).done);a=!0){var r=s.value,o=new ProductOutlet(e.cnc,r);e.outlets[o.getName()]=o,e.renderOutlet(o)}}catch(e){n=!0,i=e}finally{try{!a&&l.return&&l.return()}finally{if(n)throw i}}e.loaded=!0,e.cnc.settings.enableMap&&e.cnc.updateMap(),e.cnc.resultsLoaded()})})},{key:"getSelectedVariantIdFromHtml",value:function(){var e=document.getElementById("ProductSelect-product-template");if(e)return e.options[e.selectedIndex].value}},{key:"findGetParameter",value:function(e){var t=null,a=[];return location.search.substr(1).split("&").forEach(function(n){(a=n.split("="))[0]===e&&(t=decodeURIComponent(a[1]))}),t}}]),t}();