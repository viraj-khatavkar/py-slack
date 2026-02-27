export interface User {
    id: number;
    name: string;
    email: string | null;
    image_url: string | null;
    slack_user_id: string;
    timezone: string | null;
    timezone_label: string | null;
    is_admin: boolean;
    is_bot: boolean;
    is_deleted: boolean;
    title: string | null;
    messages_count?: number;
    created_at: string;
    updated_at: string;
}
