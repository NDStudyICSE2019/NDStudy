tinyMCEPopup.requireLangPack();

var ResourcesDialog = {
	init : function() {
		
	},

	insert : function() {
		
    tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(ResourcesDialog.init, ResourcesDialog);
