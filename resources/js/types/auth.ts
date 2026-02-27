export type User = {
    id: number;
    name: string;
    email: string | null;
    image_url: string | null;
    slack_user_id: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
