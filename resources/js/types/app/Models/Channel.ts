import type { User } from '@/types/app/Models/User';

export interface Channel {
    id: number;
    name: string;
    purpose: string | null;
    topic: string | null;
    member_count: number;
    created_at_slack: string | null;
    message_count: number;
    creator?: User;
}
