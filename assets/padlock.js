var padlock = new function(){
    this.init = function(){
        jQuery('a.editinline').click(function(){
            handleRename(jQuery(this));
            return false;
        });
    };
    
    var handleRename = function(cell) {
        var groupDiv = cell.closest('div');
        var postData = {
            groupId : groupDiv.data('gid'),
            groupName : groupDiv.attr('data-gname')
        };
        
        var newName = prompt('New group name',postData.groupName);
        if (newName) {
            postData.groupName = newName;
        }
        
        jQuery.ajax({
                type: 'POST',
                url: '/wp-content/plugins/padlock/padlock.php',
                data: postData,
                dataType: "json",
                timeout: 5000
            })
            .success(function(data){
                if (data.feedback == 'OK') {
                    groupDiv.prev('span').html(data.newname);
                    groupDiv.attr('data-gname',data.newname);
                } else {
                    alert(data.feedback);
                }
            })
            .error(function(request,status,errorThrown){
                alert(request.status);
            })
            .complete(function(){
                // hide spinner
            });
    };
};

jQuery(document).ready(padlock.init);

