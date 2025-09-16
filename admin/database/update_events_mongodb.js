use northrepublic;

// Обновление события: Дегустация вин
db.events.updateOne(
  { title: "Дегустация вин" },
  {
    $set: {
      title_ru: "Дегустация вин",
      title_en: "Wine Tasting",
      title_vi: "Nếm thử rượu vang",
      description_ru: "Дегустация лучших вин с сомелье",
      description_en: "Tasting of the best wines with sommelier",
      description_vi: "Nếm thử những loại rượu vang ngon nhất với chuyên gia rượu",
      conditions_ru: "1500 руб. с человека",
      conditions_en: "1500 rubles per person",
      conditions_vi: "1500 rúp mỗi người",
      updated_at: new Date()
    }
  }
);

// Обновление события: Новогодний банкет
db.events.updateOne(
  { title: "Новогодний банкет" },
  {
    $set: {
      title_ru: "Новогодний банкет",
      title_en: "New Year Banquet",
      title_vi: "Tiệc tất niên",
      description_ru: "Праздничный банкет с живой музыкой",
      description_en: "Holiday banquet with live music",
      description_vi: "Tiệc tất niên với nhạc sống",
      conditions_ru: "3000 руб. с человека, предварительная запись",
      conditions_en: "3000 rubles per person, advance booking required",
      conditions_vi: "3000 rúp mỗi người, cần đặt trước",
      updated_at: new Date()
    }
  }
);

// Обновление события: Мастер-класс по приготовлению пасты
db.events.updateOne(
  { title: "Мастер-класс по приготовлению пасты" },
  {
    $set: {
      title_ru: "Мастер-класс по приготовлению пасты",
      title_en: "Pasta Cooking Master Class",
      title_vi: "Lớp học nấu mì Ý",
      description_ru: "Учимся готовить настоящую итальянскую пасту",
      description_en: "Learn to cook authentic Italian pasta",
      description_vi: "Học nấu mì Ý chính thống",
      conditions_ru: "Бесплатно при заказе от 2000 руб.",
      conditions_en: "Free with order from 2000 rubles",
      conditions_vi: "Miễn phí khi đặt từ 2000 rúp",
      updated_at: new Date()
    }
  }
);

// Обновление события: Романтический ужин на День Святого Валентина
db.events.updateOne(
  { title: "Романтический ужин на День Святого Валентина" },
  {
    $set: {
      title_ru: "Романтический ужин на День Святого Валентина",
      title_en: "Romantic Valentine\'s Day Dinner",
      title_vi: "Bữa tối lãng mạn ngày Valentine",
      description_ru: "Специальное романтическое меню для влюбленных",
      description_en: "Special romantic menu for lovers",
      description_vi: "Thực đơn lãng mạn đặc biệt cho các cặp đôi",
      conditions_ru: "2500 руб. за пару, специальное меню",
      conditions_en: "2500 rubles per couple, special menu",
      conditions_vi: "2500 rúp cho cặp đôi, thực đơn đặc biệt",
      updated_at: new Date()
    }
  }
);

// Обновление события: День рождения ресторана
db.events.updateOne(
  { title: "День рождения ресторана" },
  {
    $set: {
      title_ru: "День рождения ресторана",
      title_en: "Restaurant Birthday",
      title_vi: "Sinh nhật nhà hàng",
      description_ru: "Празднование годовщины ресторана",
      description_en: "Restaurant anniversary celebration",
      description_vi: "Lễ kỷ niệm ngày thành lập nhà hàng",
      conditions_ru: "Вход свободный, специальные предложения",
      conditions_en: "Free entry, special offers",
      conditions_vi: "Vào cửa miễn phí, ưu đãi đặc biệt",
      updated_at: new Date()
    }
  }
);
