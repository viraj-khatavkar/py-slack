import dayjs from 'dayjs';

export function formatDate(
    date: string,
    format: string = 'DD MMM YYYY',
): string {
    return dayjs(date).format(format);
}

export function formatDateTime(date: string): string {
    return dayjs(date).format('DD MMM YYYY, h:mm A');
}

export function formatTime(date: string): string {
    return dayjs(date).format('h:mm A');
}

export function formatDayHeading(date: string): string {
    return dayjs(date).format('dddd, DD MMMM YYYY');
}
