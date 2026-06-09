import type { ChatPreview, FilterOptions, Message, User } from '@/types'
import { getRandomInt } from '@/misc'

export const filterOptions: FilterOptions = {
  unis: [
    'Hochschule Bochum',
    'Ruhr-Uni Bochum',
    'Uni Duisburg-Essen',
    'TU München',
    'TU Dortmund',
  ],
  courses: ['Informatik', 'Elektrotechnik', 'BWL', 'Germanistik', 'Medizin', 'Lehramt', 'Jura'],
  interests: [
    'Flugzeuge',
    'Kochen',
    'Backen',
    'Formel 1',
    'Fußball',
    'Umweltschutz',
    'Menschenrechte',
    'Kunst',
    'Musik',
  ],
  semesters: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10+'],
}

const avatar = (seed: number) => `https://i.pravatar.cc/150?img=${seed}`

export const mockStudents: User[] = [
  {
    id: 1,
    pub_name: 'Student #a3f',
    uni: 'Hochschule Bochum',
    courses: [{ name: 'Informatik', semester: 3 }],
    interests: ['Kochen', 'Fußball'],
    avatarUrl: avatar(12),
    bio: 'Suche Lernpartner für Analysis und jemanden zum Fußball schauen.',
  },
  {
    id: 2,
    pub_name: 'Student #7c2',
    uni: 'Ruhr-Uni Bochum',
    courses: [{ name: 'Elektrotechnik', semester: 5 }],
    interests: ['Formel 1', 'Flugzeuge'],
    avatarUrl: avatar(33),
    bio: 'Technik-Nerd, gerne über Projekte und Praktika austauschen.',
  },
  {
    id: 3,
    pub_name: 'Student #19e',
    uni: 'TU München',
    courses: [{ name: 'Medizin', semester: 7 }],
    interests: ['Umweltschutz', 'Kochen'],
    avatarUrl: avatar(45),
    bio: 'Medizinstudentin – suche Gleichgesinnte für Lerngruppen und entspannte Abende.',
  },
  {
    id: 4,
    pub_name: 'Student #b81',
    uni: 'Uni Duisburg-Essen',
    courses: [{ name: 'BWL', semester: 2 }],
    interests: ['Musik', 'Kunst'],
    avatarUrl: avatar(8),
    bio: 'Im zweiten Semester und noch auf der Suche nach einem guten Studienstart.',
  },
  {
    id: 5,
    pub_name: 'Student #4d0',
    uni: 'TU Dortmund',
    courses: [{ name: 'Informatik', semester: 6 }],
    interests: ['Backen', 'Menschenrechte'],
    avatarUrl: avatar(22),
    bio: 'Interessiert an Open Source und gesellschaftlichen Themen.',
  },
  {
    id: 6,
    pub_name: 'Student #f52',
    uni: 'Hochschule Bochum',
    courses: [{ name: 'Lehramt', semester: 4 }],
    interests: ['Kunst', 'Musik', 'Umweltschutz'],
    avatarUrl: avatar(51),
    bio: 'Lehramtsstudent – gerne über Didaktik und Praktika sprechen.',
  },
  {
    id: 7,
    pub_name: 'Student #2aa',
    uni: 'Ruhr-Uni Bochum',
    courses: [{ name: 'Jura', semester: 8 }],
    interests: ['Fußball', 'Formel 1'],
    avatarUrl: avatar(17),
    bio: 'Jura im Endspurt – suche Motivation und Lernpartner.',
  },
  {
    id: 8,
    pub_name: 'Student #c07',
    uni: 'TU München',
    courses: [{ name: 'Informatik', semester: 10 }],
    interests: ['Flugzeuge', 'Kochen', 'Musik'],
    avatarUrl: avatar(getRandomInt(1, 64)),
    bio: 'Master-Student, offen für Networking und Side Projects.',
  },
  {
    id: 9,
    pub_name: 'Student #88b',
    uni: 'Uni Duisburg-Essen',
    courses: [{ name: 'Germanistik', semester: 3 }],
    interests: ['Kunst', 'Backen'],
    avatarUrl: avatar(29),
    bio: 'Liebe Literatur und gutes Brot – immer offen für neue Kontakte.',
  },
  {
    id: 10,
    pub_name: 'Student #e14',
    uni: 'TU Dortmund',
    courses: [{ name: 'Elektrotechnik', semester: 1 }],
    interests: ['Fußball', 'Musik'],
    avatarUrl: avatar(41),
    bio: 'Erstes Semester – suche Anschluss und Tipps vom Studienstart.',
  },
]

export const mockChats: ChatPreview[] = [
  {
    id: 1,
    partnerId: 2,
    partnerName: 'Student #7c2',
    lastMessage: 'Klingt gut, treffen wir uns morgen in der Mensa?',
    updatedAt: new Date(Date.now() - 1000 * 60 * 12),
    unread: true,
  },
  {
    id: 2,
    partnerId: 5,
    partnerName: 'Student #4d0',
    lastMessage: 'Hast du schon die Übungsblätter für Algorithmen?',
    updatedAt: new Date(Date.now() - 1000 * 60 * 60 * 5),
    unread: false,
  },
]

export const mockMessages: Message[] = [
  {
    id: 1,
    chatId: 1,
    senderId: 2,
    text: 'Hey! Habe gesehen, dass du auch Elektrotechnik studierst.',
    sentAt: new Date(Date.now() - 1000 * 60 * 60),
  },
  {
    id: 2,
    chatId: 1,
    senderId: 0,
    text: 'Ja genau, bin im 5. Semester. Suchst du eine Lerngruppe?',
    sentAt: new Date(Date.now() - 1000 * 60 * 45),
  },
  {
    id: 3,
    chatId: 1,
    senderId: 2,
    text: 'Klingt gut, treffen wir uns morgen in der Mensa?',
    sentAt: new Date(Date.now() - 1000 * 60 * 12),
  },
  {
    id: 4,
    chatId: 2,
    senderId: 5,
    text: 'Hast du schon die Übungsblätter für Algorithmen?',
    sentAt: new Date(Date.now() - 1000 * 60 * 60 * 5),
  },
]

export const platformStats = {
  students: '12.400',
  universities: '38',
  connections: '3.000+',
  anonymous: '100%',
}
