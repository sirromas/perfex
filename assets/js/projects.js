  $(window).on('load', function() {
      fix_phases_height();
  });
  $(window).resize(function() {
      fix_phases_height();
  });

  Dropzone.options.projectFilesUpload = false;
  Dropzone.options.projectExpenseForm = false;

  var expenseDropzone;
  $(function() {
      // remove the divider for project actions in case there is no other li except for pin project
      $('ul.project-actions li:first-child').next('li.divider').remove();
      // in case invoice hash found in url
      init_invoice();
      var file_id = get_url_param('file_id');
      if (file_id) {
          view_project_file(file_id, project_id);
      }

      // Fix for shortcuts in discussions textarea/contenteditable - jquery-comments plugin
      var $discussionsContentEditable = $('#project_file_data,#discussion-comments');
      $discussionsContentEditable.on('focus','[contenteditable="true"]',function(){
          $.Shortcuts.stop();
      });
      $discussionsContentEditable.on('focusout','[contenteditable="true"]',function(){
          $.Shortcuts.start();
      });

      $('body').on('show.bs.modal', '._project_file', function() {
          discussion_comments('#project-file-discussion', discussion_id, 'file');
      });

      $('body').on('shown.bs.modal', '#milestone', function() {
          $('#milestone').find('input[name="name"]').focus();
      });

      if($('#timesheetsChart').length > 0 && typeof(project_overview_chart) != 'undefined'){
         var chartOptions = {
          type: 'bar',
          data: {},
          options: {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
              enabled: true,
              mode: 'single',
              callbacks: {
                label: function(tooltipItems, data) {
                  return decimalToHM(tooltipItems.yLabel);
                }
              }
            },
            scales: {
              yAxes: [{
                ticks: {
                  beginAtZero: true,
                  min: 0,
                  userCallback: function(label, index, labels) {
                    return decimalToHM(label);
                  },
                }
              }],
            },
          }
        };
        chartOptions.data = project_overview_chart.data;
        var ctx = document.getElementById("timesheetsChart");
        timesheetsChart = new Chart(ctx, chartOptions);
    }

      $('#project_top').on('change',function(){
        var val = $(this).val();
        var __project_group = get_url_param('group');
        if(__project_group){
          __project_group = '?group='+__project_group;
        } else {
          __project_group = '';
        }
        window.location.href = admin_url + 'projects/view/'+val+__project_group;
      });

      if(typeof(Dropbox) != 'undefined' && $('#dropbox-chooser').length > 0 ){
        document.getElementById("dropbox-chooser").appendChild(Dropbox.createChooseButton({
          success: function(files) {
           $.post(admin_url+'projects/add_external_file',{
              files:files,
              project_id:project_id,
              external:'dropbox',
              visible_to_customer: $('#pf_visible_to_customer').prop('checked')
            }).done(function(){
              var location = window.location.href;
              window.location.href= location.split('?')[0]+'?group=project_files';
          });
         },
         linkType: "preview",
         extensions: app_allowed_files.split(','),
       }));
      }

      for (var i = -10; i < $('.task-phase').not('.color-not-auto-adjusted').length / 2; i++) {
          var r = 120;
          var g = 169;
          var b = 56;
          $('.task-phase:eq(' + (i + 10) + ')').not('.color-not-auto-adjusted').css('background', color(r - (i * 13), g - (i * 13), b - (i * 13))).css('border', '1px solid ' + color(r - (i * 13), g - (i * 13), b - (i * 13)));
      };

      fix_phases_height();

      $('body').on('click', '.milestone-column .cpicker,.milestone-column .reset_milestone_color', function(e) {
          e.preventDefault();
          var color = $(this).data('color');
          var invoker = $(this);
          var milestone_id = invoker.parents('.milestone-column').data('milestone-id');
          $.post(admin_url + 'projects/change_milestone_color', {
              color: color,
              milestone_id: milestone_id
          }).done(function() {
              // Reset color needs reload
              if (color == '') {
                  window.location.reload();
              } else {
                  invoker.parents('.milestone-column').find('.reset_milestone_color').removeClass('hide');
                  invoker.parents('.milestone-column').find('.panel-heading').addClass('color-white').removeClass('task-phase');
                  invoker.parents('.milestone-column').find('.edit-milestone-phase').addClass('color-white');
              }
          })
      });

      $("body").find('.milestone-tasks-wrapper').sortable({
          connectWith: '.ms-task',
          helper: 'clone',
          items: 'li.sortable',
          placeholder: 'ui-sortable-placeholder',
          start: function(event, ui) {
              $(ui.helper).addClass('tilt');
              $(ui.helper).find('.panel-body').css('background', '#fbfbfb');
              // Start monitoring tilt direction
              tilt_direction($(ui.helper));
          },
          stop: function(event, ui) {
              $(ui.helper).removeClass("tilt");
              // Unbind temporary handlers and excess data
              $("html").unbind('mousemove', $(ui.helper).data("move_handler"));
              $(ui.helper).removeData("move_handler");
          },
          update: function(event, ui) {
              if (this === ui.item.parent()[0]) {
                  data = {};
                  data.order = [];
                  data.milestone_id = $(ui.item.parent()[0]).parents('.milestone-column').data('milestone-id');
                  data.task_id = $(ui.item).data('task-id');
                  var tasks = $(ui.item.parent()[0]).parents('.milestone-column').find('.task');
                  var i = 0;
                  $.each(tasks, function() {
                      data.order.push([$(this).data('task-id'), i]);
                      i++;
                  });
                  fix_phases_height();
                  setTimeout(function() {
                      $.post(admin_url + 'projects/update_task_milestone', data)
                  }, 50);
              }
          }
      });

      if ($('#project-files-upload').length > 0) {
          projectFilesUpload = new Dropzone('#project-files-upload', {
              paramName: "file",
              addRemoveLinks: true,
              dictFileTooBig: appLang.file_exceeds_maxfile_size_in_form,
              dictDefaultMessage: appLang.drop_files_here_to_upload,
              dictFallbackMessage: appLang.browser_not_support_drag_and_drop,
              dictRemoveFile: appLang.remove_file,
              dictCancelUpload: appLang.cancel_upload,
              acceptedFiles: app_allowed_files,
              maxFilesize: (max_php_ini_upload_size_bytes / (1024*1024)).toFixed(0),
              accept: function(file, done) {
                  done();
              },
              success: function(file, response) {
                  if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                      window.location.reload();
                  }
              },
              error: function(file, response) {
                  alert_float('danger', response);
              },
              sending: function(file, xhr, formData) {
                  formData.append("visible_to_customer", $('input[name="visible_to_customer"]').prop('checked'));
              }
          });
      }

      if ($('#project-expense-form').length > 0) {
          expenseDropzone = new Dropzone("#project-expense-form", {
              autoProcessQueue: false,
              clickable: '#dropzoneDragArea',
              acceptedFiles: app_allowed_files,
              previewsContainer: '.dropzone-previews',
              dictFileTooBig: appLang.file_exceeds_maxfile_size_in_form,
              dictDefaultMessage: appLang.drop_files_here_to_upload,
              dictFallbackMessage: appLang.browser_not_support_drag_and_drop,
              dictRemoveFile: appLang.remove_file,
              dictMaxFilesExceeded: appLang.you_can_not_upload_any_more_files,
              addRemoveLinks: true,
              maxFiles: 1,
              error: function(file, response) {
                  alert_float('danger', response);
              },
              success: function(file, response) {
                  if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                      window.location.reload();
                  }
              },

          });
      }

      _validate_form($('#project-expense-form'), {
          category: 'required',
          date: 'required',
          amount: 'required',
          currency: 'required'
      }, projectExpenseSubmitHandler);

      gantt = $("#gantt").gantt({
          source: gantt_data,
          itemsPerPage: 25,
          months: JSON.parse(months_json),
          navigate: 'scroll',
          onRender: function() {
              var rm = $('#gantt .leftPanel .name .fn-label:empty').parents('.name').css('background', 'initial');
              $('#gantt .leftPanel .spacer').html('<span class="gantt_project_name"><i class="fa fa-cubes"></i> ' + $('.project-name').text() + '</span>');
              var _percent = $('input[name="project_percent"]').val();
              $('#gantt .leftPanel .spacer').append('<div style="padding:10px 20px 10px 20px;"><div class="progress mtop5 progress-bar-mini"><div class="progress-bar progress-bar-success no-percent-text" role="progressbar" aria-valuenow="' + _percent + '" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-percent="' + _percent + '"></div></div></div>');
              init_progress_bars();
          },
          onItemClick: function(data) {
              init_task_modal(data.task_id);
          },
          onAddClick: function(dt, rowId) {
              var fmt = new DateFormatter();
              var d0 = new Date(+dt);
              var d1 = fmt.formatDate(d0, app_date_format);
              new_task(admin_url + 'tasks/task?rel_type=project&rel_id=' + project_id + '&start_date=' + d1);
          }
      });
      // Expenses additional server params
      var Expenses_ServerParams = {};
      $.each($('._hidden_inputs._filters input'), function() {
          Expenses_ServerParams[$(this).attr('name')] = '[name="' + $(this).attr('name') + '"]';
      });
      _table_api = initDataTable('.table-project-expenses', admin_url + 'projects/expenses/' + project_id, 'undefined', 'undefined', Expenses_ServerParams, [4, 'DESC']);
      if (_table_api) {
          _table_api.column(0).visible(false, false).columns.adjust();
      }
      init_rel_tasks_table(project_id, 'project');
      initDataTable('.table-notes', admin_url + 'projects/notes/' + project_id, [4], [4], 'undefined', [1, 'DESC']);
      initDataTable('.table-milestones', admin_url + 'projects/milestones/' + project_id, [2], [2]);

      var Timesheets_ServerParams = {};
      $.each($('._hidden_inputs._filters.timesheets_filters input'), function() {
          Timesheets_ServerParams[$(this).attr('name')] = '[name="' + $(this).attr('name') + '"]';
      });

     initDataTable('.table-timesheets', admin_url + 'projects/timesheets/' + project_id, [8], [8], Timesheets_ServerParams, [3, 'DESC']);
     initDataTable('.table-project-discussions', admin_url + 'projects/discussions/' + project_id, [4], [4], 'undefined', [1, 'DESC']);

      _validate_form($('#milestone_form'), {
          name: 'required',
          due_date: 'required'
      });

      _validate_form($('#copy_form'), {
          start_date: 'required',
      });

      _validate_form($('#discussion_form'), {
          subject: 'required',
      }, manage_discussion);

      var timesheet_rules = {};
      var time_sheets_form_elements = $('#timesheet_form').find('select');
      $.each(time_sheets_form_elements, function() {
          var name = $(this).attr('name');
          timesheet_rules[name] = 'required';
      });
      timesheet_rules['start_time'] = 'required';
      timesheet_rules['end_time'] = 'required';
      _validate_form($('#timesheet_form'), timesheet_rules, manage_timesheets);

      $('#discussion').on('hidden.bs.modal', function(event) {
          $('#discussion input[name="subject"]').val('');
          $('#discussion textarea[name="description"]').val('');
          $('#discussion input[name="show_to_customer"]').prop('checked', true);
          $('#discussion .add-title').removeClass('hide');
          $('#discussion .edit-title').removeClass('hide');
      });

      $('#milestone').on('hidden.bs.modal', function(event) {
          $('#additional_milestone').html('');
          $('#milestone input[name="due_date"]').val('');
          $('#milestone input[name="name"]').val('');
          $('#milestone input[name="milestone_order"]').val($('.table-milestones tbody tr').length + 1);
          $('#milestone textarea[name="description"]').val('');
          $('#milestone input[name="description_visible_to_customer"]').prop('checked', false);
          $('#milestone .add-title').removeClass('hide');
          $('#milestone .edit-title').removeClass('hide');
      });

      $('#timesheet').on('hidden.bs.modal', function(event) {
          $('#timesheet select[name="timesheet_staff_id"]').removeAttr('data-staff_id');
          $('#timesheet select[name="timesheet_staff_id"]').empty();
          $('#timesheet select[name="timesheet_staff_id"]').selectpicker('refresh');
          $('#timesheet select[name="timesheet_task_id"]').selectpicker('val', '');
          $('#timesheet textarea[name="note"]').val('');
          $('#timesheet #tags').tagit('removeAll');
          $('input[name="timer_id"]').val('');
      });

      $('#timesheet select[name="timesheet_task_id"]').on('change', function() {
          var select_staff = $('#timesheet select[name="timesheet_staff_id"]');
          var _task_id = $(this).val();
          if (_task_id == '') {
              select_staff.html('');
              select_staff.selectpicker('refresh');
              return;
          }

          var staff_id;
          if (select_staff.attr('data-staff_id')) {
              staff_id = select_staff.attr('data-staff_id');
          }
          $.get(admin_url + 'projects/timesheet_task_assignees/' + _task_id + '/' + project_id + '/' + staff_id, function(response) {
              select_staff.html(response);
              select_staff.selectpicker('refresh');
          });
      });

      $('input[name="tasks"].copy').on('change', function() {
          var checked = $(this).prop('checked');
          if (checked) {
              var copy_assignees = $('input[name="task_include_assignees"]').prop('checked');
              var copy_followers = $('input[name="task_include_followers"]').prop('checked');
              if (copy_assignees || copy_followers) {
                  $('input[name="members"].copy').prop('checked', true);
              }
              $('.copy-project-tasks-status-wrapper').removeClass('hide');
          } else {
             $('.copy-project-tasks-status-wrapper').addClass('hide');
          }
      });

      $('input[name="task_include_assignees"],input[name="task_include_followers"]').on('change', function() {
          var checked = $(this).prop('checked');
          if (checked == true) {
              $('input[name="members"].copy').prop('checked', true);
          }
      });

      $('body').on('change', '#project_invoice_select_all_tasks,#project_invoice_select_all_expenses', function() {
          var checked = $(this).prop('checked');
          var name_selector;
          if ($(this).hasClass('invoice_select_all_expenses')) {
              name_selector = 'input[name="expenses[]"]';
          } else {
              name_selector = 'input[name="tasks[]"]';
          }
          if (checked == true) {
              $(name_selector).prop('checked', true);
          } else {
              $(name_selector).prop('checked', false);
          }
      });

      $('body').on('change','input[name="invoice_data_type"]',function(){
          var val = $(this).val();
          if(val == 'timesheets_individualy'){
            $('#timesheets_bill_include_notes').removeClass('hide');
          } else {
            $('#timesheets_bill_include_notes').addClass('hide');
          }
      });

      $('input[name="members"].copy').on('change', function() {
          var checked = $(this).prop('checked');
          var checked_tasks = $('input[name="tasks"].copy').prop('checked');
          if (!checked) {
              if (checked_tasks) {
                  $('input[name="task_include_assignees"]').prop('checked', false);
                  $('input[name="task_include_followers"]').prop('checked', false);
              }
          } else {
              if (checked_tasks) {
                  $('input[name="task_include_assignees"]').prop('checked', true);
                  $('input[name="task_include_followers"]').prop('checked', true);
              }
          }
      });
  });

  function fix_phases_height() {
      if (is_mobile()) {
          return;
      }
      var maxPhaseHeight = Math.max.apply(null, $("div.tasks-phases .panel-body").map(function() {
          return $(this).outerHeight();
      }).get());
      $('div.tasks-phases .panel-body').css('min-height', maxPhaseHeight + 'px');
  }

  function milestones_switch_view() {
      $('#milestones-table').toggleClass('hide');
      $('.tasks-phases').toggleClass('hide');
      if ($('#milestones-table').hasClass('hide')) {
          $('.new-task-phase').removeClass('hide');
      } else {
          $('.new-task-phase').addClass('hide');
      }
      fix_phases_height();
  }

  function manage_discussion(form) {
      var data = $(form).serialize();
      var url = form.action;
      $.post(url, data).done(function(response) {
          response = JSON.parse(response);
          if (response.success == true) {
              alert_float('success', response.message);
          }
          $('.table-project-discussions').DataTable().ajax.reload(null,false);
          $('#discussion').modal('hide');
      });
      return false;
  }

  function manage_timesheets(form) {
      var data = $(form).serialize();
      var url = form.action;
      $.post(url, data).done(function(response) {
          response = JSON.parse(response);
          if (response.success == true) {
              alert_float('success', response.message);
          } else {
              alert_float('warning', response.message);
          }
          setTimeout(function(){
            window.location.reload();
          },1000);
      });
  }

  function edit_timesheet(invoker, id) {
      $('#timesheet select[name="timesheet_staff_id"]').attr('data-staff_id', $(invoker).attr('data-timesheet_staff_id'));
      $('select[name="timesheet_task_id"]').selectpicker('val', $(invoker).attr('data-timesheet_task_id'));
      $('input[name="timer_id"]').val(id);
      $('input[name="start_time"]').val($(invoker).attr('data-start_time'));
      $('input[name="end_time"]').val($(invoker).attr('data-end_time'));
      $('#timesheet textarea[name="note"]').val($(invoker).attr('data-note'));
      $('select[name="timesheet_task_id"]').change();

      $('#timesheet').modal('show');
      // causing problems with ui dropdown goes to top left side when modal is shown
      setTimeout(function(){
        var timesheetTags = $(invoker).attr('data-tags').split(',');
        for(var i in timesheetTags){
          $('#timesheet #tags').tagit('createTag',timesheetTags[i]);
        }
    },500);
  }

  function new_discussion() {
      $('#discussion').modal('show');
      $('#discussion .edit-title').addClass('hide');
  }

  function new_milestone() {
      $('#milestone').modal('show');
      $('#milestone .edit-title').addClass('hide');
  }

  function new_timesheet() {
      $('#timesheet').modal('show');
  }

  function edit_milestone(invoker, id) {

      var description_visible_to_customer = $(invoker).data('description-visible-to-customer');
      if (description_visible_to_customer == 1) {
          $('input[name="description_visible_to_customer"]').prop('checked', true);
      } else {
          $('input[name="description_visible_to_customer"]').prop('checked', false);
      }
      $('#additional_milestone').append(hidden_input('id', id));
      $('#milestone input[name="name"]').val($(invoker).data('name'));
      $('#milestone input[name="due_date"]').val($(invoker).data('due_date'));
      $('#milestone input[name="milestone_order"]').val($(invoker).data('order'));
      $('#milestone textarea[name="description"]').val($(invoker).data('description'));
      $('#milestone').modal('show');
      $('#milestone .add-title').addClass('hide');
  }

  function edit_discussion(invoker, id) {
      $('#additional_discussion').append(hidden_input('id', id));
      $('#discussion input[name="subject"]').val($(invoker).data('subject'));
      $('#discussion textarea[name="description"]').val($(invoker).data('description'));

      var show_to_customer = $(invoker).data('show-to-customer');
      var checked = true;
      if (show_to_customer == 0) {
          checked = false;
      }
      $('#discussion input[name="show_to_customer"]').prop('checked', checked);
      $('#discussion').modal('show');
      $('#discussion .add-title').addClass('hide');
  }

  function mass_stop_timers(only_billable) {
      $.get(admin_url + 'projects/mass_stop_timers/' + project_id + '/' + only_billable, function(response) {
          alert_float(response.type, response.message);
          setTimeout(function() {
              $('body').find('.modal-backdrop').eq(0).remove();
              init_timers();
              reload_tasks_tables();
              pre_invoice_project();
          }, 500);
      }, 'json');
  }

  function pre_invoice_project() {
      $.get(admin_url + 'projects/get_pre_invoice_project_info/' + project_id, function(response) {
          $('#pre_invoice_project').html(response);
          $('#pre_invoice_project_settings').modal('show');
      });
  }

  function invoice_project(project_id) {
      $('#pre_invoice_project_settings').modal('hide');
      var data = {};

      data.type = $('input[name="invoice_data_type"]:checked').val();
      data.timesheets_include_notes = $('input[name="timesheets_include_notes"]:checked').val();

      data.project_id = project_id;

      data.tasks = $("#tasks_who_will_be_billed input:checkbox:checked").map(function() {
          return $(this).val();
      }).get();

      data.expenses = $("#expenses_who_will_be_billed input:checkbox:checked").map(function() {
          return $(this).val();
      }).get();

      $.post(admin_url + 'projects/get_invoice_project_data/', data).done(function(response) {
          $('#invoice_project').html(response);
          $('#invoice-project-modal').modal({
              show: true,
              backdrop: 'static'
          });
      });
  }

  function delete_project_discussion(id) {
      var r = confirm(appLang.confirm_action_prompt);
      if (r == false) {
          return false;
      } else {
          $.get(admin_url + 'projects/delete_discussion/' + id, function(response) {
              alert_float(response.alert_type, response.message);
              $('.table-project-discussions').DataTable().ajax.reload(null,false);
          }, 'json');
      }
  }

  function copy_project() {
      $('#copy_project').modal('show');
  }

  function projectExpenseSubmitHandler(form) {
      $.post(form.action, $(form).serialize()).done(function(response) {
          response = JSON.parse(response);
          if (response.expenseid) {
              if (typeof(expenseDropzone) !== 'undefined') {
                  if (expenseDropzone.getQueuedFiles().length > 0) {
                      expenseDropzone.options.url = admin_url + 'expenses/add_expense_attachment/' + response.expenseid;
                      expenseDropzone.processQueue();
                  } else {
                      window.location.assign(response.url);
                  }
              } else {
                  window.location.assign(response.url);
              }
          } else {
              window.location.assign(response.url);
          }
      });
      return false;
  }

  function view_project_file(id, $project_id) {
      $('#project_file_data').empty();
      $("#project_file_data").load(admin_url + 'projects/file/' + id + '/' + project_id, function(response, status, xhr) {
          if (status == "error") {
              alert_float('danger', xhr.statusText);
          }
      });
  }

  function update_file_data(id) {
      var data = {};
      data.id = id;
      data.subject = $('body input[name="file_subject"]').val();
      data.description = $('body textarea[name="file_description"]').val();
      $.post(admin_url + 'projects/update_file_data/', data);
  }

  function project_mark_as_modal(status_id, $project_id) {
      $('#mark_tasks_finished_modal').modal('show');
      $('#project_mark_status_confirm').attr('data-status-id', status_id);
      $('#project_mark_status_confirm').attr('data-project-id', project_id);
      var $projectMarkedasFinishedInput = $('#project_marked_as_finished_email_to_contacts');
      if(status_id == 4){
        if($projectMarkedasFinishedInput.length > 0){
            $projectMarkedasFinishedInput.parents('.project_marked_as_finished').removeClass('hide');
        }
      } else {
        if($projectMarkedasFinishedInput.length > 0){
          $projectMarkedasFinishedInput.prop('checked',false);
          $projectMarkedasFinishedInput.parents('.project_marked_as_finished').addClass('hide');
        }
      }
  }
  function project_files_bulk_action(e){
    var r = confirm(appLang.confirm_action_prompt);
    if (r == false) {
        return false;
    } else {
        var mass_delete = $('#mass_delete').prop('checked');
        var ids = [];
        var data = {};
        if (mass_delete == false || typeof(mass_delete) == 'undefined') {
            data.visible_to_customer = $('#bulk_pf_visible_to_customer').prop('checked');
        } else {
            data.mass_delete = true;
        }

        var rows = $('.table-project-files').find('tbody tr');
        $.each(rows, function() {
            var checkbox = $($(this).find('td').eq(0)).find('input');
            if (checkbox.prop('checked') == true) {
                ids.push(checkbox.val());
            }
        });

        data.ids = ids;
        $(e).addClass('disabled');

        setTimeout(function() {
            $.post(admin_url + 'projects/bulk_action_files', data).done(function() {
                window.location.reload();
            });

        }, 200);
    }

  }
  function gantt_filter(){
    var status = $('select[name="gantt_task_status"]').selectpicker('val');
    var gantt_type = $('select[name="gantt_type"]').selectpicker('val');
    var params = [];
    params['gantt_type'] = gantt_type;
    params['group'] = 'project_gantt';
    if(status){
        params['gantt_task_status'] = status;
    }
    window.location.href = buildUrl(admin_url+'projects/view/'+project_id,params);
  }

  function confirm_project_status_change(e) {
      var data = {};
      $(e).attr('disabled', true);
      data.project_id = $(e).data('project-id');
      data.status_id = $(e).data('status-id');
      if(data.status_id == 4) {
        var $projectMarkedasFinishedInput = $('#project_marked_as_finished_email_to_contacts');
        if($projectMarkedasFinishedInput.length > 0){
            data.send_project_marked_as_finished_email_to_contacts = $projectMarkedasFinishedInput.prop('checked') === true ? 1 : 0;
        }
      }
      data.mark_all_tasks_as_completed = $('#mark_all_tasks_as_completed').prop('checked') === true ? 1 : 0;
      data.notify_project_members_status_change = $('#notify_project_members_status_change').prop('checked') === true ? 1 : 0;
      $.post(admin_url + 'projects/mark_as', data).done(function(response) {
          response = JSON.parse(response);
          alert_float(response.success === true ? 'success' : 'warning', response.message);
          setTimeout(function() {
              window.location.reload();
          }, 1500);
      }).fail(function(data) {
          window.location.reload();
      });
  }
