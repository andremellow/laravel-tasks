<?php

namespace Andremellow\Tasks\Enums;

enum Permission: string
{
    case TasksView = 'tasks.view';
    case TasksCreate = 'tasks.create';
    case TasksUpdate = 'tasks.update';
    case TasksDelete = 'tasks.delete';
    case TasksRestore = 'tasks.restore';
    case TasksAssign = 'tasks.assign';
    case TasksChangeStatus = 'tasks.change-status';
    case TasksManageAttachments = 'tasks.manage-attachments';
    case TaskTypesManage = 'task-types.manage';
    case TaskTagsManage = 'task-tags.manage';
}
