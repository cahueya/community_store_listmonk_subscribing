# Community Store Listmonk Subscribing

This ConcreteCMS package integrates **Community Store** with **Listmonk**, allowing you to automatically subscribe customers to one or more mailing lists after a successful purchase.

## 🚀 Features

- Automatically subscribes customers to Listmonk lists when an order is completed.
- Supports a **default list** (all customers are added automatically).
- Supports **product-specific lists** using a product attribute (`listmonk_list_id`).
- Optional **GDPR-style consent** checkbox during checkout (`listmonk_checkout_subscribe`).
- Works entirely through the Listmonk API — no external dependencies.
- Fully configurable from the ConcreteCMS dashboard.

## ⚙️ Installation

1. Copy or clone this package into your ConcreteCMS installation under  
   `/packages/community_store_listmonk_subscribing/`
2. Make sure **Community Store** is already installed and active.
3. In the ConcreteCMS dashboard, go to **Extend Concrete > Install**, and install this package.
4. A new dashboard page appears at  
   **Dashboard → Store → Listmonk Subscribing**  
   where you can enter your Listmonk API credentials.

## 🧩 Configuration

### Dashboard Settings
- **Enable Product List Subscriptions**: Turns the integration on or off.
- **Listmonk URL**: The base URL of your Listmonk instance (e.g. `https://newsletter.example.com`).
- **API User / API Key**: Credentials for the Listmonk API.
- **Default List ID** *(optional)*: If set, all customers will be added to this list after checkout.

### Product Attributes
To add customers to specific lists depending on what they buy:
1. Create a **Text Attribute** for products with handle `listmonk_list_id`.
2. Enter one or more Listmonk list IDs (comma-separated) in each product.

### Checkout Consent (optional)
If you need explicit consent before subscribing customers:
1. Create a **Checkbox Attribute** for orders with handle `listmonk_checkout_subscribe`.
2. Add it to the “Other Customer Choices” attribute group in your checkout form.

## 🧠 How It Works

When an order payment is completed:
1. The package checks whether the customer consented (if the checkbox is configured).
2. It collects all relevant List IDs:
   - Default list (if configured)
   - Product-specific lists
3. It creates or updates the subscriber in Listmonk using the API:
   - New subscribers are created and added to the lists.
   - Existing subscribers are updated and confirmed for the relevant lists.

## 🧰 Requirements

- ConcreteCMS **v9+**
- [Community Store](https://github.com/concrete5-community-store/community_store)
- PHP **8.1+**
- A running [Listmonk](https://listmonk.app/) instance with API access

## 📝 License

MIT License.

## 🤝 Contributing

Pull requests and suggestions are welcome!  
If you find bugs or want to extend functionality (e.g. campaign tracking, unsubscribe hooks, etc.), feel free to open an issue.

---

## 🙏 Credits

This add-on was inspired by the  
[community_store_mailchimp_subscribe](https://github.com/concretecms-community-store/community_store_mailchimp_subscribing)  
by [MrKarlDilkington](https://github.com/MrKarlDilkington).  
Thank you!
