class ProductETA {
  constructor(sku) {
    this.sku=sku;
    this.buttonPredecessor = '.shopify-payment-button';
    this.clientName = 'Demo';
    this.shopName = 'brn-test-demo';
    this.salesChannelId = 4;
    this.addETAButton();
  }
  addETAButton() {
    var predecessor = document.querySelector(this.buttonPredecessor);
    
    this.etaSpinner = document.createElement('div');    
    this.etaSpinner.id='spinner';
    this.etaSpinner1 = document.createElement('div'); 
    this.etaSpinner1.classList.add('bounce1');
    this.etaSpinner.appendChild(this.etaSpinner1);
    this.etaSpinner2 = document.createElement('div'); 
    this.etaSpinner2.classList.add('bounce2');
    this.etaSpinner.appendChild(this.etaSpinner2);
    this.etaSpinner3 = document.createElement('div'); 
    this.etaSpinner3.classList.add('bounce3');
    this.etaSpinner.appendChild(this.etaSpinner3);
    predecessor.parentNode.insertBefore(this.etaSpinner, predecessor.nextSibling);
    
    this.etaButton = document.createElement('button');      
    this.etaButton.id='rex-eta';
    this.etaButton.type='button';
    this.etaButton.classList.add('button');
    this.etaButton.classList.add('button--full-width');
    this.etaButton.classList.add('button--secondary');
    this.etaButton.style.marginTop = '10px';
    predecessor.parentNode.insertBefore(this.etaButton, predecessor.nextSibling);
    
    this.etaButtonText = document.createElement('span');
    this.etaButtonText.textContent = 'Check ETA';
    this.etaButton.appendChild(this.etaButtonText);
    
    this.etaButton.addEventListener('click', (event) => {
      this.getProductETA();
    });
  }
  getProductETA(){
    var parent = this;
    
    parent.etaButton.style.display = 'none';
    parent.etaSpinner.style.visibility = 'visible';   

    var url = `/apps/retailexpress/products/eta?client_name=${this.clientName}&shop_name=${this.shopName}&`;
    var qty = document.querySelector("input[name='quantity']");
    url += `sku=${this.sku}&quantity_ordered=${qty.value}&sales_channel_id=${this.salesChannelId}&available_for_preorder=true`;
    
    var etaRequest = new Request(url);

    fetch(etaRequest).then(response => response.json()).then(data => {
      parent.etaButton.style.display = 'block';
      parent.etaSpinner.style.visibility = 'hidden';

      var eta = new Date(data.data[0].eta);

      if ( eta < new Date() ) {
        parent.etaButtonText.textContent = 'Current ETA: Unknown';
      } else {        
        parent.etaButtonText.textContent = 'Current ETA: '+ new Intl.DateTimeFormat('en-AU').format(eta);
      }
    });
  }
}