let rsfpJdideal = function (formId) {

  this.formId = formId
  this.products = [this.formId]
  this.products[this.formId] = []
  this.components = []
  this.price = 0
  this.totalFieldname = ''
  this.numberDecimals = null
  this.decimal = null
  this.thousands = null
  this.taxtype = null
  this.tax = null
  this.priceFormat = /^[-]?\d+([.,]\d+)?$/
  this.randomId = null

  this.setRandomId = function (randomId) {
    this.randomId = randomId
  }

  this.setTax = function (taxtype, tax) {
    this.taxtype = taxtype
    this.tax = tax
  }

  this.setDecimals = function (numberDecimals, decimal, thousands) {
    this.numberDecimals = numberDecimals
    this.decimal = decimal
    this.thousands = thousands
  }

  this.setTotalField = function (totalFieldName) {
    this.totalFieldname = totalFieldName
  }

  this.addProduct = function (product, price) {
    let isFloat = this.priceFormat.test(price)

    if (isFloat) {
      // Check if it is already an array
      if (Object.prototype.toString.call(this.products[this.formId]) !== '[object Array]') {
        this.products[this.formId] = []
      }

      this.products[this.formId][product] = price
    }
  }

  this.addComponent = function (componentId, componentName) {
    // Check if it is already an array
    if (Object.prototype.toString.call(this.components[this.formId]) !== '[object Array]') {
      this.components[this.formId] = []
    }

    // Check if the component ID already exists
    if (this.components[this.formId].indexOf(componentId) === -1) {
      this.components[this.formId][componentId] = componentName
    }
  }

  this.calculatePrice = () => {
    if (this.products[this.formId] === undefined) {
      console.log('No products')
      console.log(this.products)
      console.log(this.formId)
      return
    }

    // Clear the price
    this.price = 0

    // Check the single product
    let singleElements = document.querySelectorAll('[data-ropayments-field=single]')

    if (singleElements.length > 0) {
      for (let index = 0; index < singleElements.length; index++) {
        let price = parseFloat(singleElements[index].value)

        if (isNaN(price) !== true) {
          this.price += price
        }
      }
    }

    // Check the input fields
    let inputElements = document.querySelectorAll('[data-ropayments-field=input]')

    if (inputElements.length > 0) {
      for (let i = 0; i < inputElements.length; i++) {
        let inputValue = inputElements[i].value

        if (inputValue.length > 0) {

          // Calculate the total price
          let fieldPrice = this.cleanPrice(inputValue)
          this.price += fieldPrice
        }
      }
    }

    // Loop through the components
    for (let componentId in this.components[this.formId]) {
      if (componentId === 'length' || !this.components[this.formId].hasOwnProperty(componentId)) {
        continue
      }

      let productName = this.components[this.formId][componentId]
      let dropdownElements = document.querySelectorAll(`select[name="form[${productName}][]"]`)
      let quantityElements = document.querySelectorAll('[name^=\"form\\[ropayments\\]\\[quantity\\]\\[' + productName + '\\]\"]')

      if (null !== dropdownElements && dropdownElements.length > 0) {
        for (let elementsIndex = 0; elementsIndex < dropdownElements.length; elementsIndex++) {
          let dropdownElement = dropdownElements[elementsIndex]
          let quantityElement = (typeof quantityElements[elementsIndex] !== 'undefined') ? quantityElements[elementsIndex] : null

          // Check the multiple selectors
          for (let index = 0; index < dropdownElement.options.length; index++) {
            if (dropdownElement.options[index].selected === true) {
              // Get the price
              let price = this.products[this.formId][`${productName}${index}|_|${dropdownElement.options[index].value}`]
              let isFloat = this.priceFormat.test(price)

              if (isFloat) {
                // Get the quantity
                let quantity = 1

                if (quantityElement !== null) {
                  quantity = quantityElement.value
                }

                // Check if we have a quantity box
                if (quantity.length > 0) {
                  price *= this.cleanPrice(quantity)
                }

                // Update the price
                this.price += parseFloat(price)
              }
            }
          }
        }
      }

      // Check the checkboxes
      let checkboxElement = document.querySelectorAll(`input[type="checkbox"][name^="form[${productName}]"]`)
      quantityElements = document.querySelectorAll('[name^=\"form\\[ropayments\\]\\[quantity\\]\\[' + productName + '\\]\"]')

      for (let index = 0; index < checkboxElement.length; index++) {
        if (checkboxElement[index].checked === true) {
          if (this.products[this.formId][productName + index + '|_|' + checkboxElement[index].value] !== undefined) {

            // Get the price
            let price = this.products[this.formId][productName + index + '|_|' + checkboxElement[index].value]
            let isFloat = this.priceFormat.test(price)

            if (isFloat) {
              // Get the quantity if needed
              let quantity = 1

              if (quantityElements.length > 0) {
                quantity = quantityElements[index].value
              }

              // Check if we have a quantity box
              if (quantity.length > 0) {
                price *= this.cleanPrice(quantity)
              }

              // Update the price
              this.price += parseFloat(price)
            }
          } else {
            console.log(this.products)
            console.log('No product found')
          }
        }
      }

      // Check the radio buttons
      let radiobuttonElement = document.querySelectorAll('input[type="radio"][name^=\"form\\[' + productName + '\\]\"]')
      quantityElements = document.querySelectorAll('[name^=\"form\\[ropayments\\]\\[quantity\\]\\[' + productName + '\\]\"]')

      for (let index = 0; index < radiobuttonElement.length; index++) {
        if (radiobuttonElement[index].checked === true) {
          if (this.products[this.formId][productName + index + '|_|' + radiobuttonElement[index].value] !== undefined) {
            // Get the price
            let price = this.products[this.formId][productName + index + '|_|' + radiobuttonElement[index].value]
            let isFloat = this.priceFormat.test(price)

            if (isFloat) {
              // Get the quantity if needed
              let quantity = 1

              if (quantityElements.length > 0) {
                quantity = quantityElements[index].value
              }

              // Check if we have a quantity box
              if (quantity.length > 0) {
                price *= this.cleanPrice(quantity)
              }

              this.price += parseFloat(price)
            }
          }
        }
      }
    }
    // Add the tax
    this.addTax()

    // Render the total
    this.showTotal()

    this.applyCoupon()
  }

  this.addTax = function () {

    if (this.price > 0) {
      this.price = (this.taxtype) ? this.price + this.tax : this.price * (this.tax / 100 + 1)
    }
  }

  this.showTotal = function () {
    // Process the visible total field
    let showTotal = document.getElementById('jdideal_total_' + this.formId)

    if (showTotal) {
      showTotal.innerHTML = this.number_format(this.price, this.numberDecimals, this.decimal, this.thousands)
    }

    // Process the hidden total field
    if (this.totalFieldname.length > 0) {
      let hiddenTotal = document.getElementById(this.totalFieldname)

      if (hiddenTotal) {
        hiddenTotal.value = this.number_format(this.price, this.numberDecimals, '.', '')
      }
    }
  }

  this.cleanPrice = function (price) {
    // Check for both period and comma, remove the period
    if (price.indexOf('.') !== -1 && price.indexOf(',') !== -1) {
      price = price.replace('.', '')
    }

    // Replace the comma with a period
    return parseFloat(price.replace(/,/g, '\.'))
  }

  // Taken from RSForms! Pro to work in module positions
  this.number_format = function (number, decimals, dec_point, thousands_sep) {
    let n = number, prec = decimals
    n = !isFinite(+n) ? 0 : +n
    prec = !isFinite(+prec) ? 0 : Math.abs(prec)
    let sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep
    let dec = (typeof dec_point === 'undefined') ? '.' : dec_point

    let s = (prec > 0) ? n.toFixed(prec) : Math.round(n).toFixed(prec) //fix for IE parseFloat(0.55).toFixed(0) = 0;

    let abs = Math.abs(n).toFixed(prec)
    let _, i

    if (abs >= 1000) {
      _ = abs.split(/\D/)
      i = _[0].length % 3 || 3

      _[0] = s.slice(0, i + (n < 0)) +
        _[0].slice(i).replace(/(\d{3})/g, sep + '$1')

      s = _.join(dec)
    } else {
      s = s.replace('.', dec)
    }

    return s
  }

  this.applyCoupon = function () {

    const discountField = document.querySelector('[data-ropayments-field=discount]')

    if (!discountField || discountField.value.length === 0 || parseInt(discountField.dataset.formid) !== this.formId) {
      return
    }

    discountField.classList.remove('payment-code-correct')

    const coupons = JSON.parse(discountField.dataset.discounts)
    const hash = CryptoJS.MD5(discountField.value).toString()

    if (!coupons.hasOwnProperty(hash)) {
      return
    }

    let discount = coupons[hash]
    if (discount.indexOf('%') > -1) {
      discount = parseFloat(discount.replace('%', ''))
      if (!isNaN(discount)) {
        this.price = ((100 - discount) / 100) * this.price
      }
    } else {
      discount = parseFloat(discount)
      if (!isNaN(discount)) {
        this.price -= discount
      }
    }

    this.showTotal()

    discountField.classList.add('payment-code-correct')
  }
}
