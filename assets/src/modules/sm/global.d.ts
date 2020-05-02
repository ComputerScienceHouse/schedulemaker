/* eslint-disable camelcase */
declare let angular: any

declare interface Course {
    id: string
    sections: Section[]
    title: string
    courseNum: string
    course: string
    department: Department
    times: Time[]
    fromSelect: boolean
    selected: boolean
    description: string
    search
}

declare interface Department {
    code: string
    number: null
}

declare interface Section {
    title: string;
    instructor: string;
    curenroll: string;
    maxenroll: string;
    courseNum: string;
    courseParentNum: string;
    courseId: string;
    id: string;
    online: boolean;
    credits: string;
    times: Time[];
    isError?: boolean
    selected?: boolean
}

declare interface Time {
    bldg: Bldg;
    room: string;
    day: string;
    start: string;
    end: string;
    off_campus: boolean;
}

declare interface Bldg {
    code: string;
    number: string;
}

declare interface ResponseError {
    error
}

declare interface Window { DD_RUM }
