export const init = () => {
  console.log("init...");
  
  // $(".tooltip").remove();
  $("[data-tooltip]").tooltip({
      title: function() {
        return $(this).data("tooltip");
      }
  });
  
  $("select:not([multiple]):visible").not(".buttons-select").not(".not-selectize").not(".no-selectize").not(".datagrid select").selectize({
    plugins: ['remove_button']
  });
  $("select[multiple]:visible").not(".buttons-select").not(".not-selectize").not(".no-selectize").not(".datagrid select").selectize({
      plugins: ['remove_button'],
      //delimiter: ';'
  });

  //$('.sticky-footer').stickyFooter();


  //** datetime picker
  var datetimepickerDefaults = {
      locale: "cs",
      useCurrent: false,
      showTodayButton: true,
      showClear: true,
      sideBySide: true,
      icons: {
          date: "fas fa-calendar text-primary",
          time: "fas fa-clock text-primary",
          today: "fas fa-home text-success",
          clear: "fas fa-times text-warning"
      },
  keyBinds: {
    right: null,
    left: null,
    up: null,
    down: null
  }
  };
  var datepicker = $.extend({}, datetimepickerDefaults, {
      format: "L"
  });
  var datetimepicker = $.extend({}, datetimepickerDefaults, {
      format: "L LT"
  });
  var timepicker = $.extend({}, datetimepickerDefaults, {
    format: "LT"
  });
  var yearpicker = $.extend({}, datetimepickerDefaults, {
    format: "Y"
  });

  $(".datepicker").datetimepicker(datepicker);
  $(".datetimepicker").datetimepicker(datetimepicker);
  $(".timepicker").datetimepicker(timepicker);
  $(".yearpicker").datetimepicker(yearpicker);

  // $(".datepicker.period-end, .datetimepicker.period-end, .datetimepicker.period-start, .datetimepicker.period-start").datetimepicker({
  //     useCurrent: false
  // });
  $(".datepicker.period-start, .datetimepicker.period-start, .datepicker.period-end, .datetimepicker.period-end").on("dp.change", function(e) {
    if (!$(this).hasClass("period-start") && !$(this).hasClass("period-end")) return; // cause of bug when manipulating inputs whole day

    var parents = $(this).parents();
    var parent = $(this).closest("form");
  var opposite = $(this).hasClass("period-start") ? "end" : "start";
      var $opposite = parent.find(".period-" + opposite);
      var limit = e.date;

  console.log(parent);
  console.log("opposite: " + opposite);
  console.log(limit);
  console.log("val: " + $opposite.val());
  
  // var first = $opposite.val() == "" ? true : false;

  if (opposite == "end") {
    // $opposite.data("DateTimePicker").date(e.date);
    $opposite.data("DateTimePicker").minDate(e.date);
  } else if (opposite == "start") {
    $opposite.data("DateTimePicker").maxDate(e.date);
  }
  });

  $("input.multidatepicker").datepicker({
    multidate: 99,
    language: "cs",
    todayBtn: true,
    multidateSeparator: ", ",
    todayHighlight: true
  });

  //** bs tooltips
  $(".bs-tooltip-top").remove();
  $("[data-toggle='tooltip']").tooltip({
    html: true
  });

$(document).on("keydown", "form.prevent-enter :input", function(e) {
  console.log(e);
  console.log(e.keyCode);
  if (e.currentTarget.tagName != "TEXTAREA" && e.keyCode === 13) e.preventDefault();
});

$(".image-popup").magnificPopup({
  type: "image",
    image: {
      titleSrc: function(item) {
        console.log("titleSrc");
        console.log(item.el);
      var el = item.el;
      var label = el.attr("title") !== undefined ? el.attr("title") : el.attr("alt");
      var desc = el.data("desc");

      var caption = "<div class='text-left text-white mb-0'>";
      caption += label !== undefined ? "<div class='font-weight-bold'>" + label + "</div>" : "";
      caption += desc !== undefined ? "<div>" + desc + "</div>" : "";
      return caption;
    }
    },
    closeBtnInside: false,
    gallery: {
      enabled: true,
    tPrev: 'Předchozí',
    tNext: 'Další',
    tCounter: '%curr% z %total%'		      
    },
  mainClass: 'gallery-image-full',
  zoom: {
    enabled: true,
    duration: 300,
    easing: 'ease-in-out'
  },
  tLoading: "Načítám...",
  tClose: "Zavřít",
  // overflowY: "hidden auto",
  fixedContentPos: true,
  closeMarkup: "<div title='%title%' class='btn btn-danger gallery-popup-close-btn'><i class='fas fa-times'></i></div>"
});

$(".draggable").draggable({
  containment: "parent",
  stop: function(event, ui) {
    // console.log(event);
    console.log(ui.helper);
    if ($(ui.helper).hasClass("draggable-percentage")) {
      console.log("percentage");
          var l = ( 100 * parseFloat($(this).position().left / parseFloat($(this).parent().width())) ) + "%" ;
          var t = ( 100 * parseFloat($(this).position().top / parseFloat($(this).parent().height())) ) + "%" ;
          $(this).css("left", l);
          $(this).css("top", t);
    }
  }
});

flashes.init();

if (window.location.hash != "") {
  var UrlHash = window.location.hash;
  var $tabBtn = $('[data-toggle="tab"][href="' + UrlHash + '"]');
  $tabBtn.tab("show");
}

// $(".ajax").addClass("ajax-active");
// $("#page-loader").fadeOut();

$.monty.bigMsg.init();

$("input.tel-mask").mask("+000 000 000 000");
$("input.price-mask").mask("000 000 000 000", {reverse: true});

  console.log("init finished...");
}