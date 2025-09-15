// MongoDB indexes for North Republic
db = db.getSiblingDB('northrepublic');

// Create indexes for menu collection
db.menu.createIndex({"categories.category_id": 1});
db.menu.createIndex({"products.menu_category_id": 1});
db.menu.createIndex({"products.sort_order": -1});

// Check existing indexes
print("Current indexes:");
db.menu.getIndexes();
