export enum ExceptionType {
    NOT_TEAM_MEMBER = 'not_team_member',
    PERSONAL_TEAM_DOES_NOT_EXIST = 'personal_team_does_not_exist',
    OTHER = 'other',
}
export type FlashData = {
    message: string;
    description?: string | null;
    type?: string | null;
    position?: string | null;
};
export type MeData = {
    name: string;
    id: string;
    first_name: string;
    last_name: string;
    email: string;
    teams: any;
    avatar: string;
};
export enum TeamPermission {
    UpdateTeam = 'team:update',
    DeleteTeam = 'team:delete',
    AddMember = 'member:add',
    UpdateMember = 'member:update',
    RemoveMember = 'member:remove',
    CreateInvitation = 'invitation:create',
    CancelInvitation = 'invitation:cancel',
}
export enum TeamRole {
    Owner = 'owner',
    Admin = 'admin',
    Member = 'member',
}
export type WebhookTypeEnum = {
    name: string;
    value: string;
};
