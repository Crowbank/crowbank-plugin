/**
 * 
 */

function PetGroup(n, className) {
	this.n = n;
	this.className = className;
	
	this.hide = function() {
		$(this.className).slideUp();
		
	}
}

function form_init() {
	var existing_customer_field = '#choice_3_7_1';
	
}

$('#choice_3_7_1').click(function(){
    if (this.checked) {
		$(".existing_customer").addClass("none_important");
		$(".gf_left_third").css({'display': 'list-item'});
		$("#field_3_5").removeClass("gfield_contains_required");
		$(".gf_right_third").addClass("display_visible_two");
		$("#field_3_8").addClass("padding_top");
		
		if($('#input_3_14 :selected').val() ==1){
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();	
			$('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==2){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: list-item');
			$(".pets_two").slideDown();
			$(".pets_three, .pets_four, .pets_five").slideUp();
			$(" .pets_two").removeClass("c_none_important");
			$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==3){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three").show();
			$(".pets_four, .pets_five").slideUp();
			$(".pets_two, .pets_three").removeClass("c_none_important");
			$(".pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_four, .pets_five").removeClass(".display_visible");
		} else if($('#input_3_14 :selected').val() ==4){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
			$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
		}
		
		
		
	    $('#input_3_14').on('change', function() {
			if( $(this).val() ==1) {
				$(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();	
				$('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==2) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: list-item');
				$(".pets_two").slideDown();
				$(".pets_three, .pets_four, .pets_five").slideUp();
				$(" .pets_two").removeClass("c_none_important");
				$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
				$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==3) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: list-item');
				$(".pets_two, .pets_three").show();
				$(".pets_four, .pets_five").slideUp();
				$(".pets_two, .pets_three").removeClass("c_none_important");
				$(".pets_four, .pets_five").addClass("c_none_important");
				$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==4) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third, .pets_four.gf_left_third").attr('style','display: list-item');
				$(".pets_two, .pets_three, .pets_four").slideDown();
				$(" .pets_five").slideUp();
				$(" .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
				$(" .pets_five").addClass("c_none_important");
				$('input[name="input_22"]').attr('checked', false);
				$(".pets_five").removeClass(".display_visible");
			} else if($(this).val() ==5) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: list-item');
				$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
				$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
			} 
	   });	
    }else{

		if($('#input_3_14 :selected').val() ==1){
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();	
			$('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==2){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: list-item');
			$(".pets_two").slideDown();
			$(".pets_three, .pets_four, .pets_five").slideUp();
			$(" .pets_two").removeClass("c_none_important");
			$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==3){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three").show();
			$(".pets_four, .pets_five").slideUp();
			$(".pets_two, .pets_three").removeClass("c_none_important");
			$(".pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==4){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
			$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
		}
		
		
		$("#field_3_8").removeClass("padding_top");
		$("#field_3_13").attr('style','visibility: hidden');
		$(".gf_right_third").removeClass("display_visible");
		$(".gf_right_third").removeClass("display_visible_two");
		$(".existing_customer").removeClass("none_important");
		$(".gf_left_third").css({'display': 'inline-block'});
		$(".gf_middle_third").attr('style','display: inline-block');
		$("#field_3_5").addClass("gfield_contains_required");
		
		$('#input_3_14').on('change', function() {
			if( $(this).val() ==1) {
			   $(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();
			   $('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			   $(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==2) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: inline-block');
				$(".pets_two").slideDown();
				$(".pets_three, .pets_four, .pets_five").slideUp();
				$(" .pets_two").removeClass("c_none_important");
				$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
				$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
			}else if($(this).val() ==3) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: inline-block');
				$(".pets_two, .pets_three").show();
				$(".pets_four, .pets_five").slideUp();
				$("  .pets_two, .pets_three").removeClass("c_none_important");
				$(".pets_four, .pets_five").addClass("c_none_important");
				$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==4) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third, .pets_four.gf_left_third").attr('style','display: inline-block');
				$(".pets_two, .pets_three, .pets_four").slideDown();
				$(" .pets_five").slideUp();
				$(" .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
				$(" .pets_five").addClass("c_none_important");
				$('input[name="input_22"]').attr('checked', false);
				$(".pets_five").removeClass(".display_visible");
			} else if($(this).val() ==5) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: inline-block');
				$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
				$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
			}
		});
    }
});
$('#input_3_14').on('change', function() {
	if( $(this).val() ==1) {
	   $(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();
	   $('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
	   $(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
	} else if($(this).val() ==2) {
		$(".pets_two").slideDown();
		$(".pets_three, .pets_four, .pets_five").slideUp();
		$(" .pets_two").removeClass("c_none_important");
		$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
		$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
		$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
	}else if($(this).val() ==3) {
		$(".pets_two, .pets_three").show();
		$(".pets_four, .pets_five").slideUp();
		$("  .pets_two, .pets_three").removeClass("c_none_important");
		$(".pets_four, .pets_five").addClass("c_none_important");
		$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
		$(".pets_four, .pets_five").removeClass(".display_visible");
	} else if($(this).val() ==4) {
		$(".pets_two, .pets_three, .pets_four").slideDown();
		$(" .pets_five").slideUp();
		$(" .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
		$(" .pets_five").addClass("c_none_important");
		$('input[name="input_22"]').attr('checked', false);
		$(".pets_five").removeClass(".display_visible");
	} else if($(this).val() ==5) {
		$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
		$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
	}
});
$('#choice_3_7_1').click(function(){
    if (this.checked) {
		$(".existing_customer").addClass("none_important");
		$(".gf_left_third").css({'display': 'list-item'});
		$("#field_3_5").removeClass("gfield_contains_required");
		$(".gf_right_third").addClass("display_visible_two");
		$("#field_3_8").addClass("padding_top");
		
		if($('#input_3_14 :selected').val() ==1){
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();	
			$('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==2){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: list-item');
			$(".pets_two").slideDown();
			$(".pets_three, .pets_four, .pets_five").slideUp();
			$(" .pets_two").removeClass("c_none_important");
			$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==3){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three").show();
			$(".pets_four, .pets_five").slideUp();
			$(".pets_two, .pets_three").removeClass("c_none_important");
			$(".pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==4){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
			$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
		}
		
		
		
	    $('#input_3_14').on('change', function() {
			if( $(this).val() ==1) {
				$(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();	
				$('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==2) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: list-item');
				$(".pets_two").slideDown();
				$(".pets_three, .pets_four, .pets_five").slideUp();
				$(" .pets_two").removeClass("c_none_important");
				$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
				$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==3) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: list-item');
				$(".pets_two, .pets_three").show();
				$(".pets_four, .pets_five").slideUp();
				$(".pets_two, .pets_three").removeClass("c_none_important");
				$(".pets_four, .pets_five").addClass("c_none_important");
				$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==4) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third, .pets_four.gf_left_third").attr('style','display: list-item');
				$(".pets_two, .pets_three, .pets_four").slideDown();
				$(" .pets_five").slideUp();
				$(" .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
				$(" .pets_five").addClass("c_none_important");
				$('input[name="input_22"]').attr('checked', false);
				$(".pets_five").removeClass(".display_visible");
			} else if($(this).val() ==5) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: list-item');
				$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
				$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
			} 
	   });	
    }else{

		if($('#input_3_14 :selected').val() ==1){
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();	
			$('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==2){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: list-item');
			$(".pets_two").slideDown();
			$(".pets_three, .pets_four, .pets_five").slideUp();
			$(" .pets_two").removeClass("c_none_important");
			$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==3){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three").show();
			$(".pets_four, .pets_five").slideUp();
			$(".pets_two, .pets_three").removeClass("c_none_important");
			$(".pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==4){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
			$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
		}
		
		
		$("#field_3_8").removeClass("padding_top");
		$("#field_3_13").attr('style','visibility: hidden');
		$(".gf_right_third").removeClass("display_visible");
		$(".gf_right_third").removeClass("display_visible_two");
		$(".existing_customer").removeClass("none_important");
		$(".gf_left_third").css({'display': 'inline-block'});
		$(".gf_middle_third").attr('style','display: inline-block');
		$("#field_3_5").addClass("gfield_contains_required");
		
		$('#input_3_14').on('change', function() {
			if( $(this).val() ==1) {
			   $(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();
			   $('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			   $(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==2) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: inline-block');
				$(".pets_two").slideDown();
				$(".pets_three, .pets_four, .pets_five").slideUp();
				$(" .pets_two").removeClass("c_none_important");
				$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
				$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
			}else if($(this).val() ==3) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: inline-block');
				$(".pets_two, .pets_three").show();
				$(".pets_four, .pets_five").slideUp();
				$("  .pets_two, .pets_three").removeClass("c_none_important");
				$(".pets_four, .pets_five").addClass("c_none_important");
				$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
				$(".pets_four, .pets_five").removeClass(".display_visible");
			} else if($(this).val() ==4) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third, .pets_four.gf_left_third").attr('style','display: inline-block');
				$(".pets_two, .pets_three, .pets_four").slideDown();
				$(" .pets_five").slideUp();
				$(" .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
				$(" .pets_five").addClass("c_none_important");
				$('input[name="input_22"]').attr('checked', false);
				$(".pets_five").removeClass(".display_visible");
			} else if($(this).val() ==5) {
				$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: inline-block');
				$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
				$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
			}
		});
    }
});
$('#input_3_14').on('change', function() {
	if( $(this).val() ==1) {
	   $(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();
	   $('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
	   $(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
	} else if($(this).val() ==2) {
		$(".pets_two").slideDown();
		$(".pets_three, .pets_four, .pets_five").slideUp();
		$(" .pets_two").removeClass("c_none_important");
		$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
		$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
		$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
	}else if($(this).val() ==3) {
		$(".pets_two, .pets_three").show();
		$(".pets_four, .pets_five").slideUp();
		$("  .pets_two, .pets_three").removeClass("c_none_important");
		$(".pets_four, .pets_five").addClass("c_none_important");
		$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
		$(".pets_four, .pets_five").removeClass(".display_visible");
	} else if($(this).val() ==4) {
		$(".pets_two, .pets_three, .pets_four").slideDown();
		$(" .pets_five").slideUp();
		$(" .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
		$(" .pets_five").addClass("c_none_important");
		$('input[name="input_22"]').attr('checked', false);
		$(".pets_five").removeClass(".display_visible");
	} else if($(this).val() ==5) {
		$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
		$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
	}
});

$(document).ready(function(){
 $(".pets_two, .pets_three, .pets_four, .pets_five").hide();
$(".pets_two, .pets_three, .pets_four, .pets_five").addClass("c_none_important");
$('input[type=radio][name=input_12]').change(function() {
	if (this.value == 'Dog') {
		$(".pets_one.gf_right_third").attr('style','visibility: visible');
		$(".pets_one.gf_right_third").addClass("gfield_contains_required");
	}
	else {
		$(".pets_one.gf_right_third").attr('style','visibility: hidden');
		$(".pets_one.gf_right_third").removeClass("gfield_contains_required");
	}
});
$('input[type=radio][name=input_21]').change(function() {
	if (this.value == 'Dog') {
		$(".pets_two.gf_right_third").addClass("gfield_contains_required display_visible");
	}
	else {
		$(".pets_two.gf_right_third").removeClass("gfield_contains_required display_visible");
	}
});
$('input[type=radio][name=input_20]').change(function() {
	if (this.value == 'Dog') {

		$(".pets_three.gf_right_third").addClass("gfield_contains_required display_visible");
	}
	else {

		$(".pets_three.gf_right_third").removeClass("gfield_contains_required display_visible");
	}
});
$('input[type=radio][name=input_19]').change(function() {
	if (this.value == 'Dog') {
		$(".pets_four.gf_right_third").addClass("gfield_contains_required display_visible");
	}
	else {
		$(".pets_four.gf_right_third").removeClass("gfield_contains_required display_visible");
	}
});
$('input[type=radio][name=input_22]').change(function() {
	if (this.value == 'Dog') {
		$(".pets_five.gf_right_third").addClass("gfield_contains_required display_visible");
	}
	else {
		$(".pets_five.gf_right_third").removeClass("gfield_contains_required display_visible");
	}
});
if($('#input_3_14 :selected').val() ==1){
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideUp();	
			$('input[name="input_21"], input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_two, .pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==2){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third").attr('style','display: list-item');
			$(".pets_two").slideDown();
			$(".pets_three, .pets_four, .pets_five").slideUp();
			$(" .pets_two").removeClass("c_none_important");
			$(" .pets_three, .pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_20"] , input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_three, .pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==3){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three").show();
			$(".pets_four, .pets_five").slideUp();
			$(".pets_two, .pets_three").removeClass("c_none_important");
			$(".pets_four, .pets_five").addClass("c_none_important");
			$('input[name="input_19"], input[name="input_22"]').attr('checked', false);
			$(".pets_four, .pets_five").removeClass(".display_visible");
		}else if($('#input_3_14 :selected').val() ==4){
			$(".pets_one.gf_left_third, .pets_two.gf_left_third, .pets_three.gf_left_third,  .pets_four.gf_left_third,  .pets_five.gf_left_third").attr('style','display: list-item');
			$(".pets_two, .pets_three, .pets_four, .pets_five").slideDown();
			$(".pets_five, .pets_four, .pets_two, .pets_three").removeClass("c_none_important");
		}
});
$(document).ready(function(){
 $(".pets_two, .pets_three, .pets_four, .pets_five").hide();
$(".pets_two, .pets_three, .pets_four, .pets_five").addClass("c_none_important");
$('input[type=radio][name=input_12]').change(function() {
	if (this.value == 'Dog') {
		$(".pets_one.gf_right_third").attr('style','visibility: visible');
		$(".pets_one.gf_right_third").addClass("gfield_contains_required");
	}
	else {
		$(".pets_one.gf_right_third").attr('style','visibility: hidden');
		$(".pets_one.gf_right_third").removeClass("gfield_contains_required");
	}
});
$('input[type=radio][name=input_21]').change(function() {
	if (this.value == 'Dog') {
		$(".pets_two.gf_right_third").addClass("gfield_contains_required display_visible");
	}
	else {
		$(".pets_two.gf_right_third").removeClass("gfield_contains_required display_visible");
	}
});
$('input[type=radio][name=input_20]').change(function() {
	if (this.value == 'Dog') {

		$(".pets_three.gf_right_third").addClass("gfield_contains_required display_visible");
	}
	else {

		$(".pets_three.gf_right_third").removeClass("gfield_contains_required display_visible");
	}
});
$('input[type=radio][name=input_19]').change(function() {
	if (this.value == 'Dog') {
		$(".pets_four.gf_right_third").addClass("gfield_contains_required display_visible");
	}
	else {
		$(".pets_four.gf_right_third").removeClass("gfield_contains_required display_visible");
	}
});
$('input[type=radio][name=input_22]').change(function() {
	if (this.value == 'Dog') {
		$(".pets_five.gf_right_third").addClass("gfield_contains_required display_visible");
	}
	else {
		$(".pets_five.gf_right_third").removeClass("gfield_contains_required display_visible");
	}
});
});
$(function () {
	var dateToday = new Date();
    $("#input_3_40").datepicker();
    $("#input_3_39").datepicker({
		 minDate: dateToday,
        onSelect: function (dateText, inst) {
            var date = $.datepicker.parseDate($.datepicker._defaults.dateFormat, dateText);
            $("#input_3_40").datepicker("option", "minDate", date)
            // the following is optional
            $("#input_3_40").datepicker("setDate", date);
        }
    });
});
