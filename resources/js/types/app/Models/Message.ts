import type { Channel } from '@/types/app/Models/Channel';
import type { User } from '@/types/app/Models/User';

export interface Reaction {
    name: string;
    users: string[];
    count: number;
}

export interface ReplyUser {
    name: string;
    image_url: string | null;
}

export interface Message {
    id: number;
    user_id: number;
    channel_id: number;
    parent_id: number | null;
    content: string;
    ts: string;
    thread_ts: string | null;
    slack_timestamp: string;
    children_count?: number;
    reactions?: Reaction[];
    is_edited: boolean;
    has_files: boolean;
    reply_users?: ReplyUser[];
    reply_users_count?: number;
    is_pinned: boolean;
    user: User;
    channel?: Channel;
    parent?: Message;
    created_at: string;
    updated_at: string;
}
