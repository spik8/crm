<script>
function countNotificationsIt() {
  $.ajax({
          type: "POST",
          url: '{{ route('api.itCountNotifications') }}',
          data: {},
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function(response) {
              var container = document.getElementById('show_notification_count');
              if (container != null) {
                container.style.visibility = "hidden";
                if(!isNaN(response) && response > 0){
                    container.style.visibility = "visible";
                    container.setAttribute('data-count',response);
                }
                else{
                    container.style.visibility = "hidden";
                }
              }
          }
      });
}

function countNotificationsCadre() {
    return $.ajax({
        type: "POST",
        url: '{{ route('api.cadreCountNotifications') }}',
        data: {},
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            var container = document.getElementById('show_notification_cadre_count');
            if (container != null) {
                container.style.visibility = "hidden";
                if(!isNaN(response) && response > 0){
                    container.style.visibility = "visible";
                    container.setAttribute('data-count',response);
                    return response;
                }
                else{
                    container.style.visibility = "hidden";
                }
            }
        }
    });
}

let ajaxRunningNotificationsIt = false;
let ajaxRunningNotificationsCadre = false;
$(document).ready(function(){
    $("#it_support").on('click', function() {
        if (ajaxRunningNotificationsIt == false) {
            ajaxRunningNotificationsIt = true;
            var department_info_id = $("#change_department").val();
            $("#it_notifications").empty();
            $("#it_support").css("pointer-events", "none");
            $.ajax({
                    type: "POST",
                    url: '{{ route('api.itSupport') }}',
                    data: {
                      "department_info_id":department_info_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        countNotificationsIt();
                        clickDisabled = true;
                        setTimeout(function(){clickDisabled = false;}, 2000);
                        if(response.length == 0) {
                            $("#it_notifications").append("<li style='margin-top:1em;  margin-bottom:1em; padding-left: 1em'>Brak nowych zgłoszeń!</li>");
                        }
                        for (var i = 0; i < response.length; i++) {
                            let title = response[i].notification_type.name;
                            let time = '';
                            if(response[i].created_at.split(' ')[0] == '{{date('Y-m-d')}}'){
                                time = response[i].created_at.split(' ')[1];
                            }else{
                                time = response[i].created_at.split(' ')[0]
                            }
                            let text = response[i].title +" ("+response[i].user.last_name+" - "+time+")";
                            let href = '{{URL::to('/show_notification/')}}'+'/' + response[i].id;
                            $("#it_notifications").append(createNotification(title,text,href, 'fa fa-exclamation-triangle'));

                        }
                        $("#it_support").css("pointer-events", "auto");
                        ajaxRunningNotificationsIt = false;
                    }
                });
        }
    });

    $("#cadre_support").on('click', function() {
        if (ajaxRunningNotificationsCadre == false) {
            ajaxRunningNotificationsCadre = true;
            var department_info_id = $("#change_department").val();
            $("#cadre_notifications").empty();
            $("#cadre_support").css("pointer-events", "none");
            $.ajax({
                type: "POST",
                url: '{{ route('api.cadreSupport') }}',
                data: {
                    "department_info_id":department_info_id
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log(response);
                    let loggedUserId = ({{Auth::user()->id}});
                    countNotificationsCadre().then(function (resolve) {
                        clickDisabled = true;
                        setTimeout(function(){clickDisabled = false;}, 2000);
                        if(resolve == 0) {
                            $("#cadre_notifications").append("<li style='margin-top:1em; margin-bottom:1em; padding-left:1em'>Brak nowych powiadomień</li>");
                        }else{
                            $.each(response, function (index, notification) {
                                let time ='';
                                let text = notification.title +" ("+notification.user.last_name+" - ";
                                if(notification.user_id == loggedUserId){
                                    let href = '{{URL::to('/view_notification/')}}'+'/' + notification.id;
                                    if(notification.notifications_changes_displayed_flags.comment_added_by_reporter_displayed === 0){

                                        if(notification.comments[notification.comments.length-1].created_at.split(' ')[0] == '{{date('Y-m-d')}}'){
                                            time = notification.comments[notification.comments.length-1].created_at.split(' ')[1];
                                        }else{
                                            time = notification.comments[notification.comments.length-1].created_at.split(' ')[0];
                                        }
                                        text += time+')';
                                        $("#cadre_notifications").append(createNotification('Dodano komentarz', text, href,'fa fa-comments'));
                                    }
                                    if(notification.notifications_changes_displayed_flags.status_change_displayed === 0){
                                        if(notification.status == 1){
                                            if(notification.created_at.split(' ')[0] == '{{date('Y-m-d')}}'){
                                                time = notification.created_at.split(' ')[1];
                                            }else{
                                                time = notification.created_at.split(' ')[0];
                                            }
                                            text += time+')';
                                            $("#cadre_notifications").append(createNotification('Zgłoszono problem', text, href,'fa fa-exclamation-triangle'));
                                        }else if(notification.status == 2){
                                            if(notification.data_start.split(' ')[0] == '{{date('Y-m-d')}}'){
                                                time = notification.data_start.split(' ')[1];
                                            }else{
                                                time = notification.data_start.split(' ')[0];
                                            }
                                            text += time+')';
                                            $("#cadre_notifications").append(createNotification('Przyjęto do realizacji', text, href,'fa fa-spinner'));
                                        }else if(notification.status == 3){
                                            if(notification.data_stop.split(' ')[0] == '{{date('Y-m-d')}}'){
                                                time = notification.data_stop.split(' ')[1];
                                            }else{
                                                time = notification.data_stop.split(' ')[0];
                                            }
                                            text += time+')';
                                            $("#cadre_notifications").append(createNotification('Zakończono realizację', text, href,'fa fa-check'));
                                        }
                                    }
                                }
                                if(notification.displayed_by == loggedUserId){
                                    let href = '{{URL::to('/show_notification/')}}'+'/' + notification.id;
                                    if(notification.notifications_changes_displayed_flags.comment_added_by_realizator_displayed === 0){
                                        if(notification.comments[notification.comments.length-1].created_at.split(' ')[0] == '{{date('Y-m-d')}}'){
                                            time = notification.comments[notification.comments.length-1].created_at.split(' ')[1];
                                        }else{
                                            time = notification.comments[notification.comments.length-1].created_at.split(' ')[0];
                                        }
                                        text += time+')';
                                        $("#cadre_notifications").append(createNotification('Dodano komentarz', text, href,'fa fa-comments'));
                                    }
                                }
                            })
                        }
                        $("#cadre_support").css("pointer-events", "auto");
                        ajaxRunningNotificationsCadre = false;
                    });
                }
            });
        }
    });
});

function createNotification(title, text, href, icon = 'fa fa-info-circle'){
    let notificationSpan = $(document.createElement('span')).css({'font-weight': 'bold','padding-left':'1em'}).text(title+': ');
    let notificationIcon =  $(document.createElement('i')).addClass(icon);
    let notificationA = $(document.createElement('a')).attr('href',href).append(notificationIcon).append(notificationSpan).append(text);
    return $(document.createElement('li')).css({'margin-top': '0.5em','margin-bottom':'0.5em'}).append(notificationA);
}
</script>
