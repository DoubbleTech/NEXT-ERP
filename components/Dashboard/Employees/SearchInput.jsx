import React from 'react'
import { Input } from '@/components/ui/input'
import { Search } from 'lucide-react'

const SearchInput = () => {
  return (
   <form >
      <div className="relative">
        <Search  color='black' className="absolute left-3 top-1/2 h-5 w-5 text-muted-foreground -translate-y-1/2" />
        <Input
          placeholder="Search employee (name, ID, designation)..."
          name="search"
          type="text"
          className="pl-10 h-12 w-full  placeholder:text-muted-foreground  rounded-2xl"
        />
      </div>
    </form>
  )
}

export default SearchInput