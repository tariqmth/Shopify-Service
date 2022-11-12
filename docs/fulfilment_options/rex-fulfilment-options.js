class FulfilmentOptions {
  constructor(settings) {
    this.settings = settings;
    this.messages = {};
  }
  addMessage(name, value, isError) {
    if (!this.messages[name]) {
      var message = new Message(this, value, isError);
      this.messages[name] = message;
      this.focResultsContainer.prepend(message.getElement());
    }
  }
  removeMessage(name) {
    if (this.messages[name]) {
      this.messages[name].getElement().outerHTML = '';
      delete this.messages[name];
    }
  }
  clearMessages() {
    for (let [name, message] of Object.entries(this.messages)) {
      this.removeMessage(name);
    }
  }
  clearErrors() {
    for (let [name, message] of Object.entries(this.messages)) {
      if (message.isError) {
        this.removeMessage(name);
      }
    }
  }
  sentenceCase (str) {
  if ((str===null) || (str===''))
       return false;
  else
   str = str.toString();
  
  return str.replace(/\w\S*/g, 
    function(txt){return txt.charAt(0).toUpperCase() +
       txt.substr(1).toLowerCase();});
  }

  searchAddress(address) {
    var mainCitiesWithCode = []; 
    mainCitiesWithCode['Sydney'] = '2000'; 
    mainCitiesWithCode['Adelaide'] = '5000';
    var mainCities= ['Sydney','Adelaide'];
    if (address==='2000' || address==='5000'){
      address='Postcode '+address+' Australia';
    }
    var formatted_address = this.sentenceCase(address);
    if (mainCities.includes(formatted_address) !== false)
    {
      address = formatted_address + ' ' + mainCitiesWithCode[formatted_address];
    }
    
    var geocoder = new google.maps.Geocoder();
    var parent = this;
    geocoder.geocode({address: address}, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        parent.mapFocus = results[0].geometry.location;
        parent.results.setLocation(parent.mapFocus.lat(), parent.mapFocus.lng());
        parent.results.update();
      } else {
        parent.results.setLocation(null, null);
        parent.addMessage('addressNotFound', address + ' not found.', true);
      }
    });
  }
}

class FulfilmentOptionsCheckout extends FulfilmentOptions {
  constructor(settings) {
    super(settings);
    this.customerAddress = this.settings.customerSuburb+', '+this.settings.customerState+', '+this.settings.customerPostcode;
    this.predecessorSelector = '.section--shipping-method';
    this.shippingSectionSelector = '.section--shipping-method .section__content .content-box';
    this.shippingMethodsSelector = '.content-box__row';
    this.shippingMethodsRadioSelector = 'div.radio__input input.input-radio';
    this.sectionClass = 'section';
    this.sectionHeaderClass = 'section__header';
    this.sectionContentClass='section__content';
    this.contentContainerClass='content-box';
    this.contentElementClass='content-box__row';
    this.pickupLabel = 'Picking up in store';
    this.pickupHeader='Pickup location';
    this.pickupUnavailableLocation='Sorry, no pickup locations are available in your area.';
    this.pickupUnavailableItems='Sorry, no pickup locations are available for all items in your cart.';
    this.pickupRadius=100;
    this.priorityLabel = 'Priority delivery';
    this.priorityHeader='Priority delivery availability';
    this.priorityAvailable='Priority delivery is available for your order!';
    this.priorityUnavailableTime='Sorry, priority delivery is not currently available. Orders must be placed before 2pm.';
    this.priorityUnavailableLocation='Sorry, priority delivery is not currently available in your area.';
    this.priorityUnavailableItems='Sorry, priority delivery is not available for all items in your cart.';
    this.priorityRadius=15;								
    this.priorityCheckAvailability = 1;
    this.priorityDeliveryCutoff = new Date();
    this.priorityDeliveryCutoff.setHours(17);
    this.priorityCostCutoff = 20;
    this.outletContactFields = {};
    this.addComponents();
    this.addMode();
    this.addOutletContactFields(['address1', 'address2', 'address3', 'suburb', 'state', 'postcode', 'phone', 'email']);
    this.addOutletNameField();
    this.addOutletNumberField();
    this.initShippingMethods();						 
    this.mapFocus = null;
    this.mapReady();
  }
  addComponents() {
    var predecessor = document.querySelector(this.predecessorSelector);
    
    this.focContainer = document.createElement('div');      
    this.focContainer.id='foc-container';
    this.focContainer.style.display='none';
    this.focContainer.classList.add(this.sectionClass);
    predecessor.parentNode.insertBefore(this.focContainer, predecessor.nextSibling);
    
    this.focHeader = document.createElement('div');    
    this.focHeader.id='foc-header';
    this.focHeader.classList.add(this.sectionHeaderClass);
    this.focContainer.appendChild(this.focHeader);

    this.focContent = document.createElement('div');    
    this.focContent.id='foc-content';
    this.focContent.classList.add(this.sectionContentClass);
    this.focContainer.appendChild(this.focContent);  

    if (this.settings.enablePickupMap) {
    	this.focMapContainer = document.createElement('div');
    	this.focMapContainer.id='foc-map-container';
    	this.focMapContainer.classList.add(this.contentContainerClass);
    	this.focContent.appendChild(this.focMapContainer);
    }

    this.focResultsContainer = document.createElement('div');
    this.focResultsContainer.id='foc-results-container';
    this.focResultsContainer.classList.add(this.contentContainerClass);
    this.focContent.appendChild(this.focResultsContainer);

    this.focSpinner = document.createElement('div');    
    this.focSpinner.id='foc-spinner';
    this.focResultsContainer.appendChild(this.focSpinner);
    this.focSpinner1 = document.createElement('div'); 
    this.focSpinner1.classList.add('bounce1');
    this.focSpinner.appendChild(this.focSpinner1);
    this.focSpinner2 = document.createElement('div'); 
    this.focSpinner2.classList.add('bounce2');
    this.focSpinner.appendChild(this.focSpinner2);
    this.focSpinner3 = document.createElement('div'); 
    this.focSpinner3.classList.add('bounce3');
    this.focSpinner.appendChild(this.focSpinner3);  
    
  }
  addMode() {
    this.modeInput = document.createElement('input');
    this.modeInput.id = `foc-mode-input`;
    this.modeInput.type = 'hidden';
    this.modeInput.name = 'checkout[attributes][cnc-fulfilment-method]';
    this.focContent.appendChild(this.modeInput);
  }
  addOutletContactFields(fieldNames) {
    for (var fieldName of fieldNames) {
      var field = new OutletContactField(fieldName);
      this.outletContactFields[fieldName] = field;
      this.focContent.appendChild(field.getElement());
    }
  }
  addOutletNumberField() {
    var field = new OutletContactField('number', 'foc-outlet', 'checkout[attributes][cnc-outlet]');
    this.outletNumberField = field;
    this.focContent.appendChild(field.getElement());
  }
  addOutletNameField() {
    var field = new OutletContactField('name', 'foc-store-name', 'checkout[attributes][cnc-store-name]');
    this.outletNameField = field;
    this.focContent.appendChild(field.getElement());
  }
  clearOutletFields() {
    for (let [key, contactField] of Object.entries(this.outletContactFields)) {
      contactField.clear();
    }
    this.outletNameField.clear();
    this.outletNumberField.clear();
  }
  addMap() {
    this.gmap = new GMap(this);
    this.focMapContainer.appendChild(this.gmap.getElement());
    this.gmap.initMap();
  }
  mapReady() {
    this.addMap();
  }
  updateMap() {
    this.gmap.searchLocationsNear(this.mapFocus, this.results.getAllOutlets());
  }
  initShippingMethods() {
    var shippingSection = document.querySelector(this.shippingSectionSelector);
    var shippingMethods = shippingSection.querySelectorAll(this.shippingMethodsSelector);

    for (var i = 0; i < shippingMethods.length; i++) {
      var shipMethRadio = shippingMethods[i].querySelector(this.shippingMethodsRadioSelector);
      
      shipMethRadio.checked=false;
      
      shipMethRadio.addEventListener('click', event => {
      	var attribute = decodeURI(event.target.value).toLowerCase();      
        
        if (attribute.includes(this.pickupLabel.toLowerCase())) {  
          document.getElementById('foc-container').style.display = '';
          this.setMode('pickup');
        } else if (attribute.includes(this.priorityLabel.toLowerCase())) {   
          document.getElementById('foc-container').style.display = '';
          this.setMode('priority');
        } else {
          document.getElementById('foc-container').style.display = 'none';
          this.setMode('home');
        }
      });
    }
    this.updateContinueButton(false);
  }
  addResults() {
    this.results = new CheckoutResults(this, this.settings.apiPrefix, this.settings.subdomain);
    this.focResultsContainer.appendChild(this.results.getElement());
  }
  getMode() {
    return this.mode;
  }
  setMode(mode) {    
    if (mode === this.mode) {
      return;
    }
    
    this.mode = mode;
    
    if (!this.results) {
      this.addResults();
    }
    
    if (document.getElementById('foc-map-container')) {
      document.getElementById('foc-map-container').style.display = 'none';
    }
    
    this.results.hide();
    this.results.unload();
    this.results.selectNone();
    this.clearOutletFields();
    this.clearMessages();
    
	if (mode === 'pickup') {
	  this.modeInput.value = 'store';
      this.searchAddress(this.customerAddress);  
      this.results.setHeader(this.pickupHeader);
      this.results.show(); 
    } 
    else if (mode === 'priority') {
	  this.modeInput.value = 'home';
      this.searchAddress(this.customerAddress);   
      this.results.setHeader(this.priorityHeader);
      this.results.show(); 
    } 
    else {
	  this.modeInput.value = 'home';
	}         
    
    this.updateContinueButton(this.results.areOutletsAvailable());
  }
  updateContactFields() {
    var selectedOutlet = this.results.getSelectedOutlet();
    if (selectedOutlet) {
      for (let [key, contactField] of Object.entries(this.outletContactFields)) {
        contactField.setValue(selectedOutlet.getContact()[key]);
      }
      this.outletNameField.setValue(selectedOutlet.getName());
      this.outletNumberField.setValue(selectedOutlet.getExternalId());
    }
  }
  updateContinueButton(available) {
    var continueButton = document.querySelector('button[id="continue_button"]') || document.querySelector('input[id="continue_button"]');
    
    if (continueButton) {      
      if ( this.mode == null) {
        continueButton.disabled = true;
      	continueButton.classList.add('btn--secondary');
      } else if ( ( this.mode === "pickup" || this.mode === "priority" ) && !available ) {
        continueButton.disabled = true;
      	continueButton.classList.add('btn--secondary');
      } else {
        continueButton.disabled = false;
      	continueButton.classList.remove('btn--secondary');
      }
    }
  }
}

class Renderable {
  getElement() {
    return this.element;
  }
  show() {
    this.element.style.display = '';
    if (this.label) {
      this.label.style.display = '';
    }
  }
  hide() {
    this.element.style.display = 'none';
    if (this.label) {
      this.label.style.display = 'none';
    }
  }
}

class Message extends Renderable {
  constructor(foc, value, isError) {
    super();
    this.foc=foc;
    this.createElement();
    this.setValue(value);
    this.setIsError(isError);
  }
  createElement() {
    this.element = document.createElement('div');
    this.element.id = 'foc-message';
    this.element.classList.add(foc.contentElementClass);
    this.paragraph = document.createElement('p');
    this.element.appendChild(this.paragraph);
  }
  getValue() {
    return this.value;
  }
  setValue(value) {
    this.value = value;
    this.paragraph.innerHTML = value;
  }
  clear() {
    this.setValue('');
  }
  setIsError(bool) {
    this.isError = bool;
    if (this.isError) {
      this.element.classList.add('foc-error-text');
    } else {
      this.element.classList.remove('foc-error-text');
    }
  }
}

class OutletContactField extends Renderable {
  constructor(name, id, elementName) {
    super();
    this.name = name;
    this.createElement(id, elementName);
  }
  createElement(id, elementName) {
    this.element = document.createElement('input');
    this.element.type = 'hidden';
    this.element.classList.add('foc-contact');
    this.element.id = id || `foc-contact-${this.name}`;
    this.element.name = elementName || `checkout[attributes][cnc-store-${this.name}]`;
    this.element.value = '';
  }
  getElement() {
    return this.element;
  }
  clear() {
    this.element.value = '';
  }
  setValue(value) {
    this.element.value = value;
  }
}

class AvailabilityTable extends Renderable {
  constructor(cartItems, stockLevels, showTable, showQuantity) {
    super();
    this.products = [];
    this.showTable = showTable;
    this.showQuantity = showQuantity;
    this.updateProducts(cartItems, stockLevels);
    this.createElement();
  }
  updateProducts(cartItems, stockLevels) {
    if (cartItems && cartItems.length) {
      for (let cartItem of cartItems) {
        var product = Object.assign({}, cartItem);
        product.outletStock = stockLevels[cartItem.variant_id];
        this.products.push(product);
      }
    } else {
      for (let [variantId, stockLevel] of Object.entries(stockLevels)) {
        var product = {
          variant_id: variantId,
          outletStock: stockLevel,
          quantity: 1
        };
        this.products.push(product);
      }
    }
  }
  isOutletAvailable() {
    for (let product of this.products) {
      if (!this.isProductAvailable(product)) {
        return false;
      }
    }
    return true;
  }
  isProductAvailable(product) {
    return product.outletStock >= product.quantity;
  }
  createElement() {
    if (this.showTable) {
      this.createTableElement();
    } else {
      this.createTextElement();
    }
  }
  createTableElement() {
    this.element = document.createElement('table');
    this.element.classList.add('foc-availability-table');
    if (this.isOutletAvailable()) {
      var headingHtml = `<tr><th colspan="3" class="foc-heading-available">All items available</th></tr>`;
    } else {
      var headingHtml = `<tr><th colspan="3" class="foc-heading-unavailable">Some items are not available</th></tr>`;
    }
    var head = document.createElement('thead');
    head.insertAdjacentHTML('beforeend', headingHtml);
    head.insertAdjacentHTML('beforeend', '<tr><th>Product</th><th>Qty</th><th>Available</th></tr>');
    this.element.appendChild(head);
    var body = document.createElement('tbody');
    for (let product of this.products) {
      if (this.isProductAvailable(product)) {
        var productTitle = product.title;
      } else if (product.outletStock > 0) {
        var productTitle = `<span class="strike">${product.title}</span> <span class="foc-negative-text">(Insufficient stock)</span>`;
      } else {
        var productTitle = `<span class="strike">${product.title}</span> <span class="foc-negative-text">(Out of stock)</span>`;
      }
      if (this.showQuantity) {
        var productAvailable = this.isProductAvailable(product)
          ? `<span class="foc-positive-text"><strong>${product.outletStock}</strong></span>`
          : `<span class="foc-negative-text"><strong>${product.outletStock}</strong></span>`;
      } else {
        var productAvailable = this.isProductAvailable(product)
          ? '<i class="fas fa-check-circle foc-positive-text"></i>'
          : '<i class="fas fa-times-circle foc-negative-text"></i>';
      }
      body.insertAdjacentHTML('beforeend', `<tr><td>${productTitle}</td><td>${product.quantity}</td><td>${productAvailable}</td></tr>`);
    }
    this.element.appendChild(body);
  }
  createTextElement() {
    this.element = document.createElement('p');
    this.element.classList.add('foc-heading-availability');
    if (this.isOutletAvailable()) {
      if (this.showQuantity && this.products.length === 1) {
        this.element.innerHTML = `${this.products[0].outletStock} available`;
      } else {
        this.element.innerHTML = 'In stock';
      }
      this.element.classList.add('foc-heading-available');
    } else {
      this.element.innerHTML = 'Out of stock';
      this.element.classList.add('foc-heading-unavailable');
    }
  }
}

class Outlet extends Renderable {
  constructor(foc, data, cartItems) {
    super();
    this.foc = foc;
    this.data = data;
    this.createAvailabilityTable(cartItems);
    this.createElement();
    this.createRadio();
    this.createLabel();
    this.radio.addEventListener('change', (event) => {
      foc.updateContactFields();
    });
  }
  getAvailability() {
    return this.availabilityTable && this.availabilityTable.isOutletAvailable();
  }
  getAvailabilityTable() {
    return this.availabilityTable;
  }
  createAvailabilityTable(cartItems) {
    this.availabilityTable = new AvailabilityTable(
      cartItems,
      this.data.stock,
      true,
      this.foc.settings.enablePickupQuantity
    );
  }
  isSelected() {
    return this.radio.checked;
  }
  select() {
    this.radio.checked = true;
  }
  deselect() {
    this.radio.checked = false;
  }
  getContact() {
    return this.data.contact;
  }
  getName() {
    return this.data.name;
  }
  getExternalId() {
    return this.data.external_id;
  }
  createElement() {
    this.element = document.createElement('div');
    this.element.classList.add('radio__input');
  }
  createRadio() {
    this.radio = document.createElement('input');
    this.radio.id = `foc-outlet-${this.data.external_id}`;
    this.radio.type = 'radio';
    this.radio.name = 'foc-outlet-element';
    this.radio.value = this.data.external_id;
    this.radio.disabled = !this.getAvailability();
    this.radio.classList.add('foc-outlet');
    this.radio.classList.add('input-radio');
    this.element.appendChild(this.radio);
    if (this.foc.getMode() === 'priority') {
      this.element.style.display='none';
    }
  }
  createLabel() {
    this.label = document.createElement('label');
    this.label.setAttribute('for', `foc-outlet-${this.data.external_id}`);
    this.label.classList.add('foc-outlet-label');
    this.label.classList.add('radio__label');
    var storeDetails = document.createElement('div');
    storeDetails.classList.add('foc-store-details');
    storeDetails.insertAdjacentHTML('beforeend', `<strong>${this.data.name}</strong><br>`);
    if (this.data.distance) {
      storeDetails.insertAdjacentHTML('beforeend', this.data.distance + 'km<br>');
    }
    storeDetails.insertAdjacentHTML('beforeend', this.getAddressHtml());
    this.label.appendChild(storeDetails);
    this.label.appendChild(this.getAvailabilityTable().getElement());
  }
  getAddressHtml() {
    var address = document.createElement('span');
    address.id = `foc-outlet-${this.data.external_id}-address`;
    var innerHtml = '';
    if (this.data.contact.address1) {
      innerHtml += this.data.contact.address1 + '<br>';
    }
    if (this.data.contact.address2) {
      innerHtml += this.data.contact.address2 + '<br>';
    }
    if (this.data.contact.address3) {
      innerHtml += this.data.contact.address3 + '<br>';
    }
    if (this.data.contact.suburb) {
      innerHtml += this.data.contact.suburb + ' ';
    }
    if (this.data.contact.state) {
      innerHtml += this.data.contact.state + ' ';
    }
    if (this.data.contact.postcode) {
      innerHtml += this.data.contact.postcode;
    }
    innerHtml += '<br>';
    if (this.data.contact.phone) {
      innerHtml += `Phone: <a href="tel:${this.data.contact.phone}">${this.data.contact.phone}</a><br>`;
    }
    if (this.data.contact.email) {
      innerHtml += `Email: <a href="mailto:${this.data.contact.email}">${this.data.contact.email}</a>`;
    }
    return innerHtml;
  }
  getLabel() {
    return this.label;
  }
}

class GMap extends Renderable {
  constructor(foc) {
    super();
    this.foc = foc;
    this.gmap;
    this.markers = [];
    this.infoWindow;
    this.locationSelect;
    this.mapInitialised = false;
    this.createElement();
  }
  createElement() {
    this.element = document.createElement('div');
    this.element.id = 'foc-map';
    this.hide();
  }
  initMap() {
    var sydney = {lat: -33.863276, lng: 151.107977};
    this.gmap = new google.maps.Map(this.element, {
      center: sydney,
      zoom: 11,
      zoomControl: true,
      mapTypeId: 'roadmap'
    });
    this.infoWindow = new google.maps.InfoWindow();
    this.mapInitialised = true;
  }
  clearLocations() {
    this.infoWindow.close();
    for (var i = 0; i < this.markers.length; i++) {
      this.markers[i].setMap(null);
    }
    this.markers.length = 0;
  }
  searchLocationsNear(center, outlets) {
    this.clearLocations();
    var bounds = new google.maps.LatLngBounds();
    for (let [name, outlet] of Object.entries(outlets)) {
      var bounds = new google.maps.LatLngBounds();
      var address = outlet.getAddressHtml();
      var distance = 0;
      var latlng = new google.maps.LatLng(
        parseFloat(outlet.getContact().latitude),
        parseFloat(outlet.getContact().longitude)
      );
      this.createMarker(latlng, name, address, outlet.data.distance);
      bounds.extend(latlng);
    }    
    if (outlets.length >= 1) {
      this.gmap.fitBounds(bounds);
      var zoom = this.gmap.getZoom();
      this.gmap.setZoom(zoom > 11 ? 11 : zoom);
    } else {
      this.gmap.setCenter(center);
    }
  }
  createMarker(latlng, name, address, distance) {
    var html = `<b>${name}</b><br>`;
    if (distance) {
      html += distance + 'km<br>';
    }
    html += address;
    var marker = new google.maps.Marker({
      map: this.gmap,
      position: latlng
    });
    var parent = this;
    google.maps.event.addListener(marker, 'click', function() {
      parent.infoWindow.setContent(html);
      parent.infoWindow.open(parent.gmap, marker);
    });
    this.markers.push(marker);
  }
}

class Results extends Renderable {
  constructor(foc, apiPrefix, subdomain) {
    super();
    this.foc = foc;
    this.apiPrefix = apiPrefix;
    this.subdomain = subdomain;
    this.outlets = {};
    this.loaded = false;
    this.lastUpdate = 0;
  }
  update() { }
  fetch() { }
  createElement() {
    this.element = document.createElement('div');
    this.element.id = 'foc-results';
    this.element.style.display = 'none';
  }
  createList() {
    this.list = document.createElement('ul');
    this.list.id = 'foc-outlets';
    this.element.appendChild(this.list);
  }
  setLocation(latitude, longitude) {
    this.latitude = latitude;
    this.longitude = longitude;
  }
  getAllOutlets() {
    return this.outlets;
  }
  count() {
    return Object.keys(this.outlets).length;
  }
  unload() {
    this.outlets = [];
    while (this.list.firstChild) {
      this.list.removeChild(this.list.firstChild);
    }
  }
  renderOutlet(outlet) {
    var listItem = document.createElement('li');
    listItem.classList.add(this.foc.contentElementClass);
    listItem.appendChild(outlet.getElement());
    listItem.appendChild(outlet.getLabel());
    this.list.appendChild(listItem);
  }
  isLoaded() {
    return this.loaded;
  }
  doOutletsExist() {
    return Object.keys(this.outlets).length > 0;
  }
  areOutletsAvailable() {
    for (let [key, outlet] of Object.entries(this.outlets)) {
      if (outlet.getAvailability()) {
        return true;
      }
    }
    return false;				 
  }
}

class CheckoutResults extends Results {
  constructor(foc, apiPrefix, subdomain) {
    super(foc, apiPrefix, subdomain);
    this.createElement();
    this.createHeader();
    this.createList();
    this.variantIds = [];
	this.orderQty = 0;
    this.cartItems = {};
  }
  createHeader() {
    var header = this.foc.focHeader;
    this.header = document.createElement('h2');
    this.header.id = 'foc-header';
    this.header.classList.add('section__title');
    header.appendChild(this.header);
  }
  setHeader(value) {
    this.header.innerHTML = value;
  }
  selectNone() {
    for (let [key, outlet] of Object.entries(this.outlets)) {
      outlet.deselect();
      this.foc.updateContactFields();
    }
  }
  selectFirst() {
    for (let [key, outlet] of Object.entries(this.outlets)) {
      if (outlet.getAvailability()) {
        outlet.select();
        this.foc.updateContactFields();
        return;
      }
    }
  }
  getSelectedOutlet() {
    for (let [key, outlet] of Object.entries(this.outlets)) {
      if (outlet.isSelected()) {
        return outlet;
      }
    }
  }
  update() {
    if (Date.now() - this.lastUpdate < 1000) {
      return;
    }
    this.lastUpdate = Date.now();
    var parent = this;
    var currentHour = new Date().getHours();											
    Shopify.getCart(function(cart) {
      var variantIds = []; 
	  var orderQty = 0;
      cart.items.forEach(function(item) {
        variantIds.push(item.variant_id);
		orderQty+=item.quantity;
      });
      parent.cartItems = cart.items;
      parent.variantIds = variantIds;
	  parent.orderQty = orderQty;
      if ( parent.foc.getMode() === 'pickup' || parent.foc.priorityDeliveryCutoff > currentHour ) {  
      	parent.fetch();
      } else {     
      	document.getElementById('foc-spinner').style.visibility = "hidden";
      	document.getElementById('foc-spinner').style.display = "none";
        document.querySelector("[data-shipping-method-label-title='Priority Delivery']").innerHTML = parent.foc.priorityLabel+' (Unavailable)';     
        parent.foc.addMessage('priorityUnavailableTime', parent.foc.priorityUnavailableTime, true);
        parent.foc.updateContinueButton(parent.areOutletsAvailable());
      }
    });
  }
  fetch() {
    this.unload();
    var parent = this;
    
    document.getElementById('foc-spinner').style.visibility = "visible";
    document.getElementById('foc-spinner').style.display = "block";
    
    var url = `https://${this.apiPrefix}/api/shopify_stores/${this.subdomain}/outlets?variant_ids=${this.variantIds.join()}`;
    
    for (let cartItem of this.cartItems) { 
      url += `&qty-${cartItem.variant_id}=${cartItem.quantity}`;
    }
    
    if ( parent.foc.getMode() === 'priority' ) {
      // Calculate hours between now and priority delivery cutoff (round to nearest hour)
      var orderTime = new Date();
      var priorityDeliverWithin = new Date();
      orderTime.setHours(orderTime.getHours() + Math.round(orderTime.getMinutes()/60));
      orderTime.setMinutes(0, 0, 0);
      
      // Set deliver within to hours from now to delivery cutoff
      priorityDeliverWithin = parent.foc.priorityDeliveryCutoff.getHours() - orderTime.getHours();
      url += `&type=ps&radius=${parent.foc.priorityRadius}&check_availability=${parent.foc.priorityCheckAvailability}&suburb=${parent.foc.settings.customerSuburb}&state=${parent.foc.settings.customerState}&postcode=${parent.foc.settings.customerPostcode}&quantity=${parent.orderQty}&deliver_within=${priorityDeliverWithin}&cost_cutoff=${parent.foc.priorityCostCutoff}`;
	} else {      
      url += `&type=cnc&radius=${parent.foc.pickupRadius}`;
    }
        
    if (this.latitude && this.longitude) {
      url += `&latitude=${this.latitude}&longitude=${this.longitude}`;
    }
    
    fetch(url)
      .then(function(response) {
        return response.json();
      })
      .then(function(json) {    
      
      	document.getElementById('foc-spinner').style.visibility = "hidden";
      	document.getElementById('foc-spinner').style.display = "none";
      
        for (let outletJson of json.outlets) {
		  var outlet = new Outlet(parent.foc, outletJson, parent.cartItems);
          parent.outlets[outlet.getName()] = outlet;
          parent.renderOutlet(outlet);
        }
      
        if (parent.foc.getMode() === 'pickup') {
          if (parent.count() < 1) {
            document.getElementById('foc-outlets').style.display='none';
            parent.foc.addMessage('pickupUnavailableLocation', parent.foc.pickupUnavailableLocation, true);
          } else if (!parent.areOutletsAvailable()) {
            document.getElementById('foc-outlets').style.display='';
            parent.foc.addMessage('pickupUnavailableItems', parent.foc.pickupUnavailableItems, true);
          } else {
            document.getElementById('foc-outlets').style.display='';
            if (parent.foc.settings.enablePickupMap) {
        	  parent.foc.gmap.show();
          	  parent.foc.focMapContainer.style.display = '';
          	  parent.foc.updateMap();
            }
          }
        }
      
        if (parent.foc.getMode() === 'priority') {
          if (parent.count() < 1) {
            document.getElementById('foc-outlets').style.display='none';
            parent.foc.addMessage('priorityUnavailableLocation', parent.foc.priorityUnavailableLocation, true);
          } else if (!parent.areOutletsAvailable()) {
            parent.foc.addMessage('priorityUnavailableItems', parent.foc.priorityUnavailableItems, true);
          } else {
            document.getElementById('foc-outlets').style.display='none';
            parent.foc.addMessage('priorityAvailable', parent.foc.priorityAvailable, false);
          }
        }
      
      	parent.selectFirst();
        parent.loaded = true;
        parent.foc.updateContinueButton(parent.areOutletsAvailable());
      });
  }
}